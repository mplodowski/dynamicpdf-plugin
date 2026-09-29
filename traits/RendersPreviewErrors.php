<?php

namespace Renatio\DynamicPDF\Traits;

use Twig\Error\Error as TwigError;

trait RendersPreviewErrors
{
    protected function previewFailedMessage(TwigError $e): string
    {
        if ($e->getPrevious() !== null) {
            report($e);
        }

        return trans('renatio.dynamicpdf::lang.templates.preview_failed', ['message' => $e->getMessage()]);
    }
}
