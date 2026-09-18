<?php

namespace Renatio\DynamicPDF\Traits;

use October\Rain\Database\Traits\Translatable;

/**
 * Per-language content, off until the multisite feature is enabled so an installation
 * that does not ask for it is untouched.
 */
trait TranslatesContent
{
    use Translatable;

    public function isTranslatableEnabled(): bool
    {
        return (bool) config('multisite.features.renatio_dynamicpdf_template', false);
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
