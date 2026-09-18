<?php

namespace Renatio\DynamicPDF\Traits;

use October\Rain\Database\Traits\Translatable;
use Renatio\DynamicPDF\Classes\PDFManager;

/**
 * Per-language content, off until the multisite feature is enabled so an installation
 * that does not ask for it is untouched.
 */
trait TranslatesContent
{
    use Translatable;

    abstract public function getView(): ?string;

    abstract public function fillFromView(string $path): void;

    abstract public function isCustomised(): bool;

    public function isTranslatableEnabled(): bool
    {
        return (bool) config('multisite.features.renatio_dynamicpdf_template', false);
    }

    /**
     * Fills the base attributes from the locale's sibling view for one render, so a stored
     * translation still wins and nothing is saved.
     */
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
     * Runs the callback against the base value, so the view file and the sync never
     * write into a translation.
     *
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
