<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('Registered view fallback', function () {
    it('builds a template from its registered view when the record does not exist', function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);

        $template = Template::byCode('renatio.dynamicpdf::pdf.invoice');

        expect($template->exists)->toBeFalse()
            ->and($template->title)->toBe('Invoice')
            ->and($template->content_html)->toContain('Invoice');
    });

    it('builds a layout from its registered view when the record does not exist', function () {
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);

        $layout = Layout::byCode('renatio.dynamicpdf::pdf.layouts.default');

        expect($layout->exists)->toBeFalse()
            ->and($layout->name)->toBe('Default Layout');
    });

    it('still throws for a code that is neither stored nor registered', function () {
        expect(fn () => Template::byCode('non.existent::pdf.template'))->toThrow(ModelNotFoundException::class);
    });
});
