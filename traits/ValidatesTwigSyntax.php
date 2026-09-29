<?php

namespace Renatio\DynamicPDF\Traits;

use Renatio\DynamicPDF\Classes\PDF;
use Twig\Error\Error as TwigError;

/**
 * October runs beforeValidate on forceSave() too, so the view sync is skipped explicitly.
 */
trait ValidatesTwigSyntax
{
    /**
     * @return array<string, string>
     */
    protected function twigSyntaxErrors(): array
    {
        if ($this->validationForced || ! $this->content_html || ! $this->isDirty('content_html')) {
            return [];
        }

        try {
            PDF::checkSyntax($this->content_html, (string) $this->code);
        } catch (TwigError $e) {
            return ['content_html' => trans('renatio.dynamicpdf::lang.templates.twig_invalid', [
                'line' => $e->getTemplateLine(),
                'message' => $e->getRawMessage(),
            ])];
        }

        return [];
    }
}
