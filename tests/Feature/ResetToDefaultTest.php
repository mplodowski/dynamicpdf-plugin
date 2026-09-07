<?php

use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('Reset to default', function () {
    beforeEach(function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
    });

    afterEach(fn () => PDFManager::forgetInstance());

    it('restores a customised template from its view and makes it follow the view again', function () {
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'title' => 'Edited', 'content_html' => '<p>edited</p>', 'is_custom' => true]);

        (new Templates)->update_onResetDefault($template->id);

        $stored = Template::byCode('renatio.dynamicpdf::pdf.invoice');

        expect($stored->is_custom)->toBeFalse()
            ->and($stored->title)->toBe('Invoice')
            ->and($stored->content_html)->toContain('Invoice');
    });

    it('marks a template saved through the form as customised', function () {
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);

        (new Templates)->formBeforeSave($template);

        expect($template->is_custom)->toBeTrue();
    });

    it('restores a locked layout from its view', function () {
        $layout = $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.layouts.default', 'name' => 'Edited', 'content_html' => '<html><body>edited</body></html>', 'is_locked' => true]);

        (new Layouts)->update_onResetDefault($layout->id);

        expect(Layout::byCode('renatio.dynamicpdf::pdf.layouts.default')->name)->toBe('Default Layout');
    });
});
