<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Str;
use Renatio\DynamicPDF\Models\Template;
use stdClass;

/**
 * @phpstan-type Entry array{label: string, snippet: string, description: string|null}
 */
class TemplateVariables
{
    protected const IDENTIFIER = '/^[A-Za-z_]\w*$/';

    protected const FILTERS = [
        '|pdfasset' => ['pdfasset', "{{ 'plugins/acme/shop/assets/img/logo.png'|pdfasset }}"],
        '|app' => ['app', "{{ 'storage/app/media/logo.png'|app }}"],
        '|_' => ['translate', "{{ 'acme.shop::lang.invoice.title'|_ }}"],
        '|raw' => ['raw', '{{ value|raw }}'],
        '|date' => ['date', "{{ value|date('Y-m-d') }}"],
        '|number_format' => ['number_format', "{{ value|number_format(2, '.', ' ') }}"],
        '|nl2br' => ['nl2br', '{{ value|nl2br }}'],
    ];

    /**
     * Twig's own loop variables, word operators, tests and literals: never a loop variable, and a key with one of these names is read through ['…'].
     */
    protected const TWIG_RESERVED = [
        'loop', '_key', '_seq', '_parent', '_context', '_self', '_charset',
        'in', 'not', 'and', 'or', 'xor', 'is', 'matches', 'starts', 'ends', 'with', 'has', 'some', 'every',
        'b-and', 'b-or', 'b-xor', 'same', 'as', 'divisible', 'by', 'defined', 'empty', 'even', 'odd',
        'iterable', 'constant', 'true', 'false', 'null', 'none',
    ];

    /** @var array<string, true> */
    protected array $taken = [];

    /**
     * @return array{sample: list<Entry>|null, globals: list<Entry>, filters: list<Entry>}
     */
    public function forTemplate(Template $template): array
    {
        $sample = json_decode((string) $template->sample_data);
        $globals = array_diff_key(
            PDFManager::instance()->listRegisteredVariables(),
            array_flip(TemplateRenderer::RESERVED_VARIABLES),
        );

        $this->taken = array_fill_keys(array_map('strval', [
            ...array_keys($sample instanceof stdClass ? get_object_vars($sample) : []),
            ...array_keys($globals),
            ...TemplateRenderer::RESERVED_VARIABLES,
        ]), true);

        return [
            'sample' => $this->sampleEntries($sample),
            'globals' => $this->globalEntries($globals),
            'filters' => $this->filterEntries(),
        ];
    }

    /**
     * @return list<Entry>|null
     */
    protected function sampleEntries(mixed $sample): ?array
    {
        if (! $sample instanceof stdClass || get_object_vars($sample) === []) {
            return null;
        }

        $entries = [];
        $this->walk($sample, '', '', [], $entries);

        return $entries === [] ? null : $entries;
    }

    /**
     * @param  array<string, mixed>  $globals
     * @return list<Entry>
     */
    protected function globalEntries(array $globals): array
    {
        $entries = [];

        foreach ($globals as $name => $value) {
            $this->walk($this->toJsonShape($value), (string) $name, $this->access('', $name), [], $entries);
        }

        $entries[] = $this->entry('locale', '{{ locale }}', trans('renatio.dynamicpdf::lang.variables.locale'));

        return $entries;
    }

    /**
     * Gives a registered PHP value the shape json_decode() gives sample data; closures and objects stay opaque.
     */
    protected function toJsonShape(mixed $value): mixed
    {
        if (! is_array($value)) {
            return is_object($value) ? null : $value;
        }

        $value = array_map($this->toJsonShape(...), $value);

        return array_is_list($value) ? $value : (object) $value;
    }

    /**
     * @return list<Entry>
     */
    protected function filterEntries(): array
    {
        $entries = [];

        foreach (self::FILTERS as $label => [$langKey, $snippet]) {
            $entries[] = $this->entry($label, $snippet, trans("renatio.dynamicpdf::lang.variables.filter.{$langKey}"));
        }

        return $entries;
    }

    /**
     * @param  list<array{string, string}>  $loops  [variable, source] of every enclosing for loop
     * @param  list<Entry>  $entries
     */
    protected function walk(mixed $value, string $label, string $expression, array $loops, array &$entries): void
    {
        if ($value instanceof stdClass) {
            foreach (get_object_vars($value) as $key => $child) {
                $childLabel = $label === '' ? (string) $key : "{$label}.{$key}";
                $this->walk($child, $childLabel, $this->access($expression, $key), $loops, $entries);
            }

            return;
        }

        if (! is_array($value)) {
            $entries[] = $this->entry($label, $this->wrap($loops, "{{ {$expression} }}"));

            return;
        }

        $variable = $this->loopVariable($label, $loops);
        $loops[] = [$variable, $expression];
        $objects = array_values(array_filter($value, fn (mixed $item): bool => $item instanceof stdClass));
        $lists = array_values(array_filter($value, is_array(...)));

        if (count($objects) + count($lists) < count($value) || $value === []) {
            $this->walk(null, $label . '[]', $variable, $loops, $entries);
        }

        if ($objects !== []) {
            $this->walk(array_reduce($objects, $this->mergeObjects(...), new stdClass), $label . '[]', $variable, $loops, $entries);
        }

        if ($lists !== []) {
            $this->walk(array_merge(...$lists), $label . '[]', $variable, $loops, $entries);
        }
    }

    protected function mergeObjects(stdClass $merged, stdClass $item): stdClass
    {
        foreach (get_object_vars($item) as $key => $value) {
            $current = $merged->{$key} ?? null;

            $merged->{$key} = match (true) {
                $current instanceof stdClass && $value instanceof stdClass => $this->mergeObjects(clone $current, $value),
                is_array($current) && is_array($value) => array_merge($current, $value),
                default => $value,
            };
        }

        return $merged;
    }

    protected function access(string $expression, int|string $key): string
    {
        if (is_string($key) && preg_match(self::IDENTIFIER, $key) && ! $this->isReserved($key)) {
            return $expression === '' ? $key : "{$expression}.{$key}";
        }

        $index = is_int($key) ? (string) $key : "'" . addcslashes($key, "'\\") . "'";

        return ($expression === '' ? '_context' : $expression) . "[{$index}]";
    }

    /**
     * @param  list<array{string, string}>  $loops
     */
    protected function loopVariable(string $label, array $loops): string
    {
        $name = Str::afterLast(str_replace('[]', '', $label), '.');
        $singular = Str::singular($name);
        $taken = $this->taken + array_fill_keys(array_column($loops, 0), true);
        $candidates = ['item'];

        if (preg_match(self::IDENTIFIER, $singular)) {
            $candidates = $singular === $name ? [$singular . '_item', 'item'] : [$singular, $singular . '_item', 'item'];
        }

        foreach ($candidates as $candidate) {
            if (! isset($taken[$candidate]) && ! $this->isReserved($candidate)) {
                return $candidate;
            }
        }

        $i = 2;

        while (isset($taken["item{$i}"])) {
            $i++;
        }

        return "item{$i}";
    }

    protected function isReserved(string $name): bool
    {
        return in_array(strtolower($name), self::TWIG_RESERVED, true);
    }

    /**
     * @param  list<array{string, string}>  $loops
     */
    protected function wrap(array $loops, string $snippet): string
    {
        foreach (array_reverse($loops) as [$variable, $source]) {
            $snippet = "{% for {$variable} in {$source} %}{$snippet}{% endfor %}";
        }

        return $snippet;
    }

    /**
     * @return Entry
     */
    protected function entry(string $label, string $snippet, ?string $description = null): array
    {
        return ['label' => $label, 'snippet' => $snippet, 'description' => $description];
    }
}
