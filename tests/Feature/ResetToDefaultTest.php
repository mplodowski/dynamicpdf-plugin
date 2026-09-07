<?php

use Illuminate\Support\Facades\DB;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Layout;

describe('Reset to default', function () {
    beforeEach(function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
    });

    afterEach(fn () => PDFManager::forgetInstance());

    it('restores a customised template from its view and makes it follow the view again', function () {
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'title' => 'Edited', 'content_html' => '<p>edited</p>', 'is_custom' => true]);

        (new Templates)->update_onResetDefault($template->id);

        $row = DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'renatio.dynamicpdf::pdf.invoice')->first();

        expect($row?->is_custom)->toBeFalsy()
            ->and((string) $row?->title)->toBe('Invoice')
            ->and((string) $row?->content_html)->toContain('Invoice');
    });

    it('restores a locked layout from its view', function () {
        $layout = $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.layouts.default', 'name' => 'Edited', 'content_html' => '<html><body>edited</body></html>', 'is_locked' => true]);

        (new Layouts)->update_onResetDefault($layout->id);

        expect(Layout::byCode('renatio.dynamicpdf::pdf.layouts.default')->name)->toBe('Default Layout');
    });
});
