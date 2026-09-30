<?php

use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;

describe('View status notice', function () {
    beforeEach(function () {
        actingAsPdfManager();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
    });

    it('informs on a template following its view and warns on a customised one', function () {
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);

        expect((new Templates)->run('update', [$template->id])->getContent())
            ->toContain('This template follows the view file <code>renatio.dynamicpdf::pdf.invoice</code>')
            ->toContain('callout-info')
            ->not->toContain('callout-warning');

        $template->is_custom = true;
        $template->save();

        expect((new Templates)->run('update', [$template->id])->getContent())
            ->toContain('This template no longer follows the view file')
            ->toContain('callout-warning');
    });

    it('informs on a layout following its view and warns on an edited one', function () {
        $layout = $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.layouts.default', 'is_locked' => true]);

        expect((new Layouts)->run('update', [$layout->id])->getContent())
            ->toContain('This layout follows the view file <code>renatio.dynamicpdf::pdf.layouts.default</code>')
            ->toContain('callout-info')
            ->not->toContain('callout-warning');

        $layout->is_locked = false;
        $layout->save();

        expect((new Layouts)->run('update', [$layout->id])->getContent())
            ->toContain('This layout no longer follows the view file')
            ->toContain('callout-warning');
    });

    it('shows no notice on a record created in the backend', function () {
        $content = (new Templates)->run('update', [$this->createTemplate()->id])->getContent();

        expect($content)->not->toContain('the view file')
            ->and($content)->toContain('Test Template');
    });
});
