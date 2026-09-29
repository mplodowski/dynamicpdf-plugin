<?php

namespace Renatio\DynamicPDF\Traits;

use Twig\Error\Error as TwigError;

trait RendersPreviewErrors
{
    /**
     * The Twig message names the template or layout code and the line, so an editor can find the mistake.
     */
    protected function previewFailedMessage(TwigError $e): string
    {
        return trans('renatio.dynamicpdf::lang.templates.preview_failed', ['message' => $e->getMessage()]);
    }
}
