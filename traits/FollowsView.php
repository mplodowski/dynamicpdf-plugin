<?php

namespace Renatio\DynamicPDF\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use October\Rain\Database\Traits\Translatable;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\FailureLog;
use System\Classes\SiteManager;
use Throwable;

/**
 * @method static Builder<static> followingView()
 *
 * @mixin \October\Rain\Database\Model
 */
trait FollowsView
{
    use Translatable;

    protected bool $viewReadFailed = false;

    /**
     * @return array<string, string>
     */
    abstract protected static function registeredViews(): array;

    abstract public function fillFromView(string $code): void;

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

    public function afterFetch(): void
    {
        if ($this->isCustomised() || ! $this->code || ! $this->getView()) {
            return;
        }

        $this->inDefaultLocale(function (): void {
            $attributes = $this->getAttributes();
            $relations = $this->getRelations();

            try {
                $this->fillFromView($this->code);
                $this->viewReadFailed = false;
            } catch (Throwable $e) {
                $this->setRawAttributes($attributes, true);
                $this->setRelations($relations);

                $this->failedToReadView($e);
            }
        });
    }

    public static function normalizeViewValue(mixed $value): string
    {
        $value = str_replace("\r\n", "\n", trim((string) $value));

        return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : $value;
    }

    public function isCustomised(): bool
    {
        return $this->exists && (bool) $this->is_custom;
    }

    public function markAsCustomised(): void
    {
        $this->is_custom = true;
    }

    public function markAsFollowingView(): void
    {
        $this->is_custom = false;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFollowingView(Builder $query): Builder
    {
        return $query->where('is_custom', false);
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
            throw new ApplicationException(trans("renatio.dynamicpdf::lang.{$this->langGroup()}.not_found", ['code' => $this->code]));
        }

        $this->fillFromView($view);
    }

    public function getView(): ?string
    {
        return Arr::get(static::registeredViews(), $this->code);
    }

    public function hasView(): bool
    {
        return (bool) $this->getView();
    }

    /**
     * The stored values stand in for an unreadable view, so a sync must not report them as current.
     */
    public function viewReadFailed(): bool
    {
        return $this->viewReadFailed;
    }

    protected function failedToReadView(Throwable $e): void
    {
        $this->viewReadFailed = true;

        FailureLog::instance()->logOnce(static::class, $this->code, "Renatio.DynamicPDF could not read the view of {$this->code}: {$e->getMessage()}", $e);
    }

    public function isTranslatableEnabled(): bool
    {
        return (bool) config('multisite.features.renatio_dynamicpdf_template', false);
    }

    public function fillFromLocalizedView(?string $locale): void
    {
        if (! $locale || ! $this->isTranslatableEnabled() || $this->isCustomised()) {
            return;
        }

        $view = $this->getView();
        $localizedView = $view === null ? null : $this->findLocalizedView($view, $locale);

        if ($localizedView === null) {
            return;
        }

        $code = $this->code;

        $this->inDefaultLocale(fn () => $this->fillFromView($localizedView));

        $this->code = $code;
    }

    protected function findLocalizedView(string $view, string $locale): ?string
    {
        $namespace = '';

        if (($position = strpos($view, '::')) !== false) {
            $namespace = substr($view, 0, $position + 2);
            $view = substr($view, $position + 2);
        }

        foreach (SiteManager::instance()->getLocaleKeyChain($locale) as $localeKey) {
            $segments = explode('.', $view);
            array_splice($segments, -1, 0, $localeKey);
            $candidate = $namespace . implode('.', $segments);

            if (View::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function inDefaultLocale(callable $callback): mixed
    {
        if (! $this->shouldTranslate()) {
            return $callback();
        }

        $locale = $this->getLocale();
        $this->setLocale($this->getTranslatableDefault());

        try {
            return $callback();
        } finally {
            $this->setLocale($locale);
        }
    }

    /**
     * @param  object  $fields
     */
    protected function describeViewStatus($fields): void
    {
        if (! isset($fields->_view_status)) {
            return;
        }

        if (! $this->hasView()) {
            $fields->_view_status->hidden();

            return;
        }

        $customised = $this->isCustomised();
        $key = match (true) {
            $customised => 'view_detached',
            $this->shouldTranslate() => 'view_follows_translation',
            default => 'view_follows',
        };

        $fields->_view_status
            ->mode($customised ? 'warning' : 'info')
            ->comment(trans("renatio.dynamicpdf::lang.{$this->langGroup()}.{$key}", [
                'view' => $this->getView(),
                'reset' => trans('backend::lang.form.reset_default'),
            ]));
    }

    protected function langGroup(): string
    {
        return strtolower(class_basename(static::class));
    }
}
