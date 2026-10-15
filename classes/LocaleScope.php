<?php

namespace Renatio\DynamicPDF\Classes;

use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\SiteManager;

class LocaleScope
{
    public function __construct(protected ?string $locale)
    {
        if ($this->locale === '') {
            $this->locale = null;
        }
    }

    /**
     * @param  array<int, Template|Layout|null>  $models
     * @param  array<string, mixed>  $data
     * @param  callable(array<string, mixed>): string  $render
     */
    public function render(array $models, array $data, callable $render): string
    {
        foreach ($models as $model) {
            $this->applyTranslateContext($model);
        }

        $data = array_merge(['locale' => $this->locale ?? app()->getLocale()], $data);

        if ($this->locale === null) {
            return $render($data);
        }

        $previous = app()->getLocale();
        app()->setLocale($this->locale);

        try {
            return $render($data);
        } finally {
            app()->setLocale($previous);
        }
    }

    protected function applyTranslateContext(Template|Layout|null $model): void
    {
        if ($this->locale === null || $model === null || ! $model->exists || ! $model->isTranslatableEnabled()) {
            return;
        }

        foreach (SiteManager::instance()->getLocaleKeyChain($this->locale) as $localeKey) {
            if ($model->hasTranslations($localeKey)) {
                $model->setLocale($localeKey);

                return;
            }
        }

        $model->setLocale($this->locale);
    }
}
