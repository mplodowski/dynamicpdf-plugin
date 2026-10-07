<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Facades\Flash;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Layout;

describe('Reset to default', function () {
    beforeEach(function () {
        actingAsPdfManager();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
    });

    it('restores a customised template from its view and makes it follow the view again', function () {
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'title' => 'Edited', 'content_html' => '<p>edited</p>', 'is_custom' => true]);

        (new Templates)->update_onResetDefault($template->id);

        $row = DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'renatio.dynamicpdf::pdf.invoice')->first();

        expect($row?->is_custom)->toBeFalsy()
            ->and((string) $row?->title)->toBe('Invoice')
            ->and((string) $row?->content_html)->toContain('Invoice');
    });

    it('restores an edited layout from its view and makes it follow the view again', function () {
        $layout = $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.layouts.default', 'name' => 'Edited', 'content_html' => '<html><body>edited</body></html>', 'is_locked' => false]);

        (new Layouts)->update_onResetDefault($layout->id);

        $stored = Layout::byCode('renatio.dynamicpdf::pdf.layouts.default');

        expect($stored->name)->toBe('Default Layout')
            ->and($stored->is_locked)->toBeTrue();
    });

    it('refuses to reset a template without a view with the same message as the list', function () {
        $template = $this->createTemplate();

        expect(fn () => (new Templates)->update_onResetDefault($template->id))
            ->toThrow(ApplicationException::class, trans('renatio.dynamicpdf::lang.templates.reset_view_only'));
    });

    it('flashes the reset message unescaped because the backend layout escapes it after the redirect', function () {
        App::setLocale('fr');
        $layout = $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.layouts.default', 'is_locked' => false]);

        (new Layouts)->update_onResetDefault($layout->id);

        expect(Flash::all()['success'] ?? null)->toBe(trans('renatio.dynamicpdf::lang.templates.reset_success'))
            ->toContain("L'enregistrement");
    });
});
