<?php

namespace Renatio\DynamicPDF\Classes;

use BackedEnum;
use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JsonSerializable;
use October\Rain\Database\Attach\File;
use Stringable;
use Traversable;
use Twig\Markup;
use UnitEnum;

/**
 * Hands the sandboxed template plain values, so its own data cannot run a query or save.
 */
class TwigData
{
    protected const MAX_DEPTH = 12;

    /** @var array<int, true> */
    protected array $path = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function convert(array $data): array
    {
        return $this->list($data, 0);
    }

    protected function value(mixed $value, int $depth): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $this->list($value, $depth);
        }

        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof DateTimeInterface => Carbon::instance($value),
            $value instanceof Markup => $value,
            $value instanceof Closure, $value instanceof EloquentBuilder, $value instanceof QueryBuilder, $value instanceof Relation => null,
            default => $this->object($value, $depth),
        };
    }

    protected function object(object $object, int $depth): mixed
    {
        $id = spl_object_id($object);

        if ($depth >= self::MAX_DEPTH || isset($this->path[$id])) {
            return null;
        }

        $this->path[$id] = true;

        try {
            return match (true) {
                $object instanceof Model => $this->model($object, $depth),
                $object instanceof Traversable => $this->list(iterator_to_array($object), $depth),
                $object instanceof Arrayable => $this->list($object->toArray(), $depth),
                $object instanceof JsonSerializable => $this->value($object->jsonSerialize(), $depth + 1),
                $object instanceof Stringable => (string) $object,
                default => $this->list(get_object_vars($object), $depth),
            };
        } finally {
            unset($this->path[$id]);
        }
    }

    /**
     * Visible attributes as the model returns them, dates as Carbon, and only relations already loaded.
     *
     * @return array<string, mixed>
     */
    protected function model(Model $model, int $depth): array
    {
        $keys = array_keys($model->getAttributes());
        $relations = $model->getRelations();
        $appends = $model->getAppends();

        if ($model instanceof File) {
            $appends[] = 'url';
        }

        $result = [];

        foreach ($this->visible($model, array_unique([...$keys, ...$appends])) as $key) {
            $result[$key] = $this->value($model->getAttribute($key), $depth + 1);
        }

        foreach ($this->visible($model, array_keys($relations)) as $key) {
            $result[$key] = $this->value($relations[$key], $depth + 1);
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    protected function visible(Model $model, array $keys): array
    {
        if ($visible = $model->getVisible()) {
            $keys = array_intersect($keys, $visible);
        }

        return array_diff($keys, $model->getHidden());
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    protected function list(array $values, int $depth): array
    {
        foreach ($values as $key => $value) {
            $values[$key] = $this->value($value, $depth + 1);
        }

        return $values;
    }
}
