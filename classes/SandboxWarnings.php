<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use October\Rain\Support\Traits\Singleton;

/**
 * A model reaches the sandbox as an array, so a method, a lazy relation or an accessor outside
 * $appends reads as null without an error. This names the cause in the log instead.
 */
class SandboxWarnings
{
    use Singleton;

    protected string $template = '';

    /** @var array<string, Model> keyed by the attribute names of the array each became */
    protected array $models = [];

    /** @var array<string, true> */
    protected array $logged = [];

    public function start(string $template): void
    {
        $this->template = $template;
        $this->models = [];
    }

    /**
     * @param  array<string, mixed>  $converted
     */
    public function remember(Model $model, array $converted): void
    {
        $this->models[$this->signature($converted)] ??= $model;
    }

    /**
     * @param  array<mixed>  $array
     */
    public function missing(array $array, string $item, int $line): void
    {
        $model = $this->models[$this->signature($array)] ?? null;
        $reason = $model === null ? null : $this->reason($model, $item);

        if ($reason === null) {
            return;
        }

        $key = "{$this->template}|{$line}|{$item}";

        if (isset($this->logged[$key])) {
            return;
        }

        $this->logged[$key] = true;
        $class = $model::class;

        Log::warning("Renatio.DynamicPDF sandbox: \"{$item}\" in {$this->template} on line {$line} is null, because {$class}::{$item} is {$reason}. See the 9.0.0 upgrade guide.");
    }

    /**
     * @param  array<mixed>  $array
     */
    protected function signature(array $array): string
    {
        return implode("\0", array_keys($array));
    }

    protected function reason(Model $model, string $item): ?string
    {
        return match (true) {
            method_exists($model, 'hasRelation') && $model->hasRelation($item) => 'a relation that was not eager-loaded',
            $model->hasGetMutator($item) || $model->hasAttributeMutator($item) => 'an accessor missing from $appends',
            $this->hasMethod($model, $item) => 'a method, which the sandbox does not call',
            default => null,
        };
    }

    protected function hasMethod(Model $model, string $item): bool
    {
        foreach (['', 'get', 'is', 'has'] as $prefix) {
            if (method_exists($model, $prefix . $item)) {
                return true;
            }
        }

        return false;
    }
}
