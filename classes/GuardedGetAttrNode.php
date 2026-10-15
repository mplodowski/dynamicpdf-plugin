<?php

namespace Renatio\DynamicPDF\Classes;

use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use System\Twig\Node\GetAttrNode;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Sandbox\SecurityError;
use Twig\Source;
use Twig\Template;

/**
 * Template data reaches the sandbox as arrays, so the objects left are dates, markup and what functions
 * and globals return. GetAttrNode compiles to static::customGetAttribute(), so this override sees each
 * attribute read and method call on them first. Models, queries and relations only give up attributes
 * and relations already loaded.
 */
class GuardedGetAttrNode extends GetAttrNode
{
    /** @var array<class-string, array<string, string>> */
    protected static array $methodMaps = [];

    /** @var array<string, ReflectionMethod> */
    protected static array $reflections = [];

    /**
     * @param  mixed  $object
     * @param  string|int  $item
     * @param  array<int|string, mixed>  $arguments
     * @param  string  $type
     * @param  bool  $isDefinedTest
     * @param  bool  $ignoreStrictCheck
     * @param  bool  $sandboxed
     */
    public static function customGetAttribute(Environment $env, Source $source, $object, $item, array $arguments = [], $type = Template::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false, $sandboxed = false, int $lineno = -1): mixed
    {
        if ($object instanceof Model) {
            if (static::callsModelMethod($object, (string) $item, $type)) {
                throw new SecurityError("Calling the \"{$item}\" method of a model is not allowed in PDF templates.", $lineno, $source);
            }

            if ($isDefinedTest) {
                return static::isVisible($object, (string) $item) && ($object->relationLoaded((string) $item) || static::isModelAttribute($object, (string) $item));
            }

            return static::modelValue($object, (string) $item);
        }

        if ($type === Template::ARRAY_CALL) {
            return CoreExtension::getAttribute($env, $source, $object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck, $sandboxed, $lineno);
        }

        if (! $isDefinedTest && is_object($object) && ($denied = static::denied($object, (string) $item, $arguments, $type)) !== null) {
            throw new SecurityError("{$denied} is not allowed in PDF templates.", $lineno, $source);
        }

        return parent::customGetAttribute($env, $source, $object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck, $sandboxed, $lineno);
    }

    protected static function callsModelMethod(Model $model, string $item, string $type): bool
    {
        if ($type !== Template::ANY_CALL) {
            return $type === Template::METHOD_CALL;
        }

        $isRelation = $model->relationLoaded($item) || (method_exists($model, 'hasRelation') && $model->hasRelation($item));

        if ($isRelation || static::isModelAttribute($model, $item)) {
            return false;
        }

        $methods = static::$methodMaps[$model::class] ??= static::methodMap($model);

        return isset($methods[$item]) || isset($methods[strtolower($item)]);
    }

    /**
     * Unlike getAttribute(), never lazy-loads a relation.
     */
    protected static function modelValue(Model $model, string $item): mixed
    {
        if (! static::isVisible($model, $item)) {
            return null;
        }

        if ($model->relationLoaded($item)) {
            return $model->getRelation($item);
        }

        return static::isModelAttribute($model, $item) ? $model->getAttributeValue($item) : null;
    }

    protected static function isVisible(Model $model, string $item): bool
    {
        $visible = $model->getVisible();

        return ! in_array($item, $model->getHidden(), true) && ($visible === [] || in_array($item, $visible, true));
    }

    protected static function isModelAttribute(Model $model, string $item): bool
    {
        return array_key_exists($item, $model->getAttributes()) || $model->hasGetMutator($item) || $model->hasAttributeMutator($item);
    }

    /**
     * @param  array<int|string, mixed>  $arguments
     */
    protected static function denied(object $object, string $item, array $arguments, string $type): ?string
    {
        if (($type === Template::ANY_CALL && static::readsValue($object, $item))) {
            return null;
        }

        if (array_filter(array_keys($arguments), is_string(...)) !== []) {
            return "Passing named arguments to the \"{$item}\" method";
        }

        if ($object instanceof EloquentBuilder || $object instanceof QueryBuilder || $object instanceof Relation) {
            return "Calling the \"{$item}\" method of a query";
        }

        $methods = static::$methodMaps[$object::class] ??= static::methodMap($object);
        $method = $methods[$item] ?? $methods[strtolower($item)] ?? null;

        if ($object instanceof EloquentCollection && preg_match('/^(load|fresh$|toquery$)/i', $method ?? $item)) {
            return "Calling the \"{$item}\" method of a model collection";
        }

        if ($method === null && ! isset($methods['__call'])) {
            return null;
        }

        $reflection = $method === null ? null : static::reflectMethod($object, $method);

        if ($reflection?->isStatic()) {
            return "Calling the static \"{$item}\" method";
        }

        if (! $object instanceof Collection && static::passesCallable($reflection, $arguments)) {
            return "Passing a PHP callable to the \"{$item}\" method";
        }

        return null;
    }

    /**
     * Decided without isset(), which would run magic methods before the check.
     */
    protected static function readsValue(object $object, string $item): bool
    {
        return array_key_exists($item, get_object_vars($object))
            || ($object instanceof DateTimeInterface && in_array($item, ['date', 'timezone', 'timezone_type'], true))
            || defined($object::class . '::' . $item);
    }

    protected static function reflectMethod(object $object, string $method): ReflectionMethod
    {
        return static::$reflections[$object::class . '::' . $method] ??= new ReflectionMethod($object, $method);
    }

    /**
     * Same lookup table as Twig\Extension\CoreExtension::getAttribute(), which keeps its own private.
     *
     * @return array<string, string>
     */
    protected static function methodMap(object $object): array
    {
        $methods = get_class_methods($object);

        if ($object instanceof Closure) {
            $methods[] = '__invoke';
        }

        sort($methods);
        $lcMethods = array_map(strtolower(...), $methods);
        $map = [];

        foreach ($methods as $i => $method) {
            $lcName = $lcMethods[$i];
            $map[$method] = $method;
            $map[$lcName] = $method;

            if (str_starts_with($lcName, 'get')) {
                $prefixLength = 3;
            } elseif (str_starts_with($lcName, 'is')) {
                $prefixLength = 2;
            } elseif (str_starts_with($lcName, 'has') && ! in_array('is' . substr($lcName, 3), $lcMethods, true)) {
                $prefixLength = 3;
            } else {
                continue;
            }

            $lcName = substr($lcName, $prefixLength);

            if ($lcName !== '') {
                $map[$lcName[0] . substr($method, $prefixLength + 1)] ??= $method;
                $map[$lcName] ??= $method;
            }
        }

        return $map;
    }

    /**
     * A parameter typed without callable takes the string as a value; any other, and a method reached
     * through __call, may call it.
     *
     * @param  array<int|string, mixed>  $arguments
     */
    protected static function passesCallable(?ReflectionMethod $method, array $arguments): bool
    {
        $parameters = $method?->getParameters() ?? [];
        $last = end($parameters);

        foreach (array_values($arguments) as $position => $argument) {
            if (! static::containsCallable($argument)) {
                continue;
            }

            $parameter = $parameters[$position] ?? ($last && $last->isVariadic() ? $last : null);

            if ($parameter === null || static::acceptsCallable($parameter->getType())) {
                return true;
            }
        }

        return false;
    }

    protected static function containsCallable(mixed $value): bool
    {
        if ($value instanceof Closure) {
            return false;
        }

        if (is_callable($value)) {
            return true;
        }

        return is_array($value) && array_filter($value, static::containsCallable(...)) !== [];
    }

    protected static function acceptsCallable(?ReflectionType $type): bool
    {
        if ($type === null) {
            return true;
        }

        $types = $type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType ? $type->getTypes() : [$type];

        foreach ($types as $named) {
            if ($named instanceof ReflectionNamedType && in_array($named->getName(), ['callable', 'mixed'], true)) {
                return true;
            }
        }

        return false;
    }
}
