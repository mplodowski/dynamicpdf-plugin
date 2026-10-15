<?php

namespace Renatio\DynamicPDF\Traits;

use Renatio\DynamicPDF\Classes\TwigRenderer;
use Twig\Error\Error as TwigError;

/**
 * @mixin \October\Rain\Database\Model
 */
trait ValidatesContent
{
    public const CODE_FORMAT = 'regex:#^[\w.:/-]+$#';

    /**
     * A code stored before the format rule existed is exempt: the form offers the field only on
     * create, so rejecting it later would block every edit of that row.
     */
    protected function exemptStoredCodeFromFormat(): void
    {
        if ($this->exists && ! $this->isDirty('code')) {
            $this->removeValidationRule('code', 'regex');
        }
    }

    /**
     * October runs beforeValidate on forceSave() too, so the view sync is skipped explicitly.
     *
     * @return array<string, string>
     */
    protected function twigSyntaxErrors(): array
    {
        if ($this->validationForced || ! $this->content_html || ! $this->isDirty('content_html')) {
            return [];
        }

        try {
            (new TwigRenderer)->checkSyntax($this->content_html, (string) $this->code);
        } catch (TwigError $e) {
            return ['content_html' => trans('renatio.dynamicpdf::lang.templates.twig_invalid', [
                'line' => $e->getTemplateLine(),
                'message' => $e->getRawMessage(),
            ])];
        }

        return [];
    }
}
