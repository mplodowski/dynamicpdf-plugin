<?php

namespace Renatio\DynamicPDF\Classes;

use Closure;
use Illuminate\Support\Str;
use Renatio\DynamicPDF\Models\Template;

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
     * @return array{sample: list<Entry>|null, globals: list<Entry>, filters: list<Entry>}
     */
    public function forTemplate(Template $template): array
    {
        return [
            'sample' => $this->sampleEntries($template->sample_data),
            'globals' => $this->globalEntries(),
            'filters' => $this->filterEntries(),
        ];
    }

    /**
     * @return list<Entry>|null
     */
    protected function sampleEntries(?string $json): ?array
    {
        $data = json_decode((string) $json, true);

        if (! is_array($data) || $data === [] || array_is_list($data)) {
            return null;
        }

        $entries = [];
        $this->walk($data, '', '', [], $entries);

        return $entries;
    }

    /**
     * @return list<Entry>
     */
    protected function globalEntries(): array
    {
        $entries = [];
        $variables = array_diff_key(
            PDFManager::instance()->listRegisteredVariables(),
            array_flip(TemplateRenderer::RESERVED_VARIABLES),
        );

        foreach ($variables as $name => $value) {
            $this->walk($value instanceof Closure ? null : $value, (string) $name, $this->access('', $name), [], $entries);
        }

        $entries[] = $this->entry('locale', '{{ locale }}', trans('renatio.dynamicpdf::lang.variables.locale'));

        return $entries;
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
        if (! is_array($value)) {
            $entries[] = $this->entry($label, $this->wrap($loops, "{{ {$expression} }}"));

            return;
        }

        if ($value !== [] && ! array_is_list($value)) {
            foreach ($value as $key => $child) {
                $childLabel = $label === '' ? (string) $key : "{$label}.{$key}";
                $this->walk($child, $childLabel, $this->access($expression, $key), $loops, $entries);
            }

            return;
        }

        $variable = $this->loopVariable($label, $loops);
        $loops[] = [$variable, $expression];
        $rows = array_filter($value, is_array(...));

        $this->walk($rows === [] ? null : array_replace_recursive(...$rows), $label . '[]', $variable, $loops, $entries);
    }

    protected function access(string $expression, int|string $key): string
    {
        if (is_string($key) && preg_match(self::IDENTIFIER, $key)) {
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
        $variable = $singular !== $name && preg_match(self::IDENTIFIER, $singular) ? $singular : 'item';
        $taken = array_column($loops, 0);
        $candidate = $variable;

        for ($i = 2; in_array($candidate, $taken, true); $i++) {
            $candidate = $variable . $i;
        }

        return $candidate;
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
