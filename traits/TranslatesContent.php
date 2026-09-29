<?php

namespace Renatio\DynamicPDF\Traits;

use October\Rain\Database\Traits\Translatable;
use Renatio\DynamicPDF\Classes\PDFManager;

trait TranslatesContent
{
    use Translatable;

    abstract public function getView(): ?string;

    abstract public function fillFromView(string $code): void;

    abstract public function isCustomised(): bool;

    public function isTranslatableEnabled(): bool
    {
        return (bool) config('multisite.features.renatio_dynamicpdf_template', false);
    }

    public function fillFromLocalizedView(?string $locale): void
    {
        if (! $locale || $this->isCustomised() || ! $this->isTranslatableEnabled()) {
            return;
        }

        $view = $this->getView();
        $localizedView = $view === null ? null : PDFManager::instance()->findLocalizedView($view, $locale);

        if ($localizedView === null) {
            return;
        }

        $code = $this->code;

        $this->inDefaultLocale(fn () => $this->fillFromView($localizedView));

        $this->code = $code;
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
}
