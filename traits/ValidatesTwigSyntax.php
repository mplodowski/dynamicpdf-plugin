<?php

namespace Renatio\DynamicPDF\Traits;

use Renatio\DynamicPDF\Classes\PDF;
use Twig\Error\Error as TwigError;

/**
 * Markup is parsed but never rendered, so a save needs no sample data and runs no code.
 * The view sync force-saves developer markup, which October still passes through
 * beforeValidate, so a forced save is not checked.
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
