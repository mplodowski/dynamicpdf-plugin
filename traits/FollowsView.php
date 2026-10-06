<?php

namespace Renatio\DynamicPDF\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use October\Rain\Exception\ApplicationException;

/**
 * @mixin \October\Rain\Database\Model
 */
trait FollowsView
{
    /**
     * @return array<string, string>
     */
    abstract protected static function registeredViews(): array;

    abstract public function fillFromView(string $code): void;

    abstract protected function markAsFollowingView(): void;

    public static function byCode(string $code): self
    {
        $model = static::whereCode($code)->first();

        if ($model instanceof static) {
            return $model;
        }

        if (! array_key_exists($code, static::registeredViews())) {
            throw (new ModelNotFoundException)->setModel(static::class, [$code]);
        }

        $model = new self;
        $model->markAsFollowingView();
        $model->fillFromView($code);

        return $model;
    }

    public function resetToView(): void
    {
        $this->inDefaultLocale(function (): void {
            $this->fillFromCode();
            $this->markAsFollowingView();
            $this->save();
        });
    }

    public function fillFromCode(): void
    {
        $view = $this->getView();

        if (! $view) {
            $group = strtolower(class_basename(static::class));

            throw new ApplicationException(e(trans("renatio.dynamicpdf::lang.{$group}.not_found", ['code' => $this->code])));
        }

        $this->fillFromView($view);
    }

    public function getView(): ?string
    {
        return Arr::get(static::registeredViews(), $this->code);
    }

    public function followsView(): bool
    {
        return (bool) $this->getView();
    }
}
