<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('Registered view fallback', function () {
    beforeEach(function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
    });

    afterEach(fn () => PDFManager::forgetInstance());

    it('builds a template from its registered view when the record does not exist', function () {
        $template = Template::byCode('renatio.dynamicpdf::pdf.invoice');

        expect($template->exists)->toBeFalse()
            ->and($template->title)->toBe('Invoice')
            ->and($template->layout?->name)->toBe('Default Layout');
    });

    it('renders the template inside its registered layout', function () {
        $html = app('dynamicpdf')->parseTemplate(Template::byCode('renatio.dynamicpdf::pdf.invoice'));

        expect($html)->toContain('<!DOCTYPE html>')
            ->and($html)->toContain('<h1 class="text-4xl font-bold leading-none">Invoice</h1>');
    });

    it('builds a layout from its registered view when the record does not exist', function () {
        $layout = Layout::byCode('renatio.dynamicpdf::pdf.layouts.default');

        expect($layout->exists)->toBeFalse()
            ->and($layout->code)->toBe('renatio.dynamicpdf::pdf.layouts.default')
            ->and($layout->name)->toBe('Default Layout');
    });

    it('still throws for a code that is neither stored nor registered', function () {
        expect(fn () => Template::byCode('non.existent::pdf.template'))->toThrow(ModelNotFoundException::class);
    });
});
