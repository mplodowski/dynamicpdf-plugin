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

    it('warns on a template following its view and points a customised one to reset', function () {
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);

        expect((new Templates)->run('update', [$template->id])->getContent())
            ->toContain('This record follows the view file');

        $template->is_custom = true;
        $template->save();

        expect((new Templates)->run('update', [$template->id])->getContent())
            ->toContain('This record no longer follows the view file');
    });

    it('warns on a layout following its view and points an edited one to reset', function () {
        $layout = $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.layouts.default', 'is_locked' => true]);

        expect((new Layouts)->run('update', [$layout->id])->getContent())
            ->toContain('This record follows the view file');

        $layout->is_locked = false;
        $layout->save();

        expect((new Layouts)->run('update', [$layout->id])->getContent())
            ->toContain('This record no longer follows the view file');
    });

    it('shows no notice on a record created in the backend', function () {
        $content = (new Templates)->run('update', [$this->createTemplate()->id])->getContent();

        expect($content)->not->toContain('follows the view file')
            ->and($content)->toContain('Test Template');
    });
});
