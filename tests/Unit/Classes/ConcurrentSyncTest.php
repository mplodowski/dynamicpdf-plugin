<?php

use Illuminate\Support\Facades\DB;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Template;

describe('Unique codes', function () {
    afterEach(function () {
        PDFManager::forgetInstance();
        SyncTemplates::forgetFailures();
    });

    it('stores a code once even when two syncs race', function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        (new SyncTemplates)->handle();

        $late = new Template;
        $late->fillFromView('renatio.dynamicpdf::pdf.invoice');

        expect(fn () => $late->forceSave())->toThrow(Illuminate\Database\QueryException::class)
            ->and(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->count())->toBe(1);
    });

    it('refuses a duplicate layout code at the database level', function () {
        $this->createLayout(['code' => 'acme::pdf.layouts.default']);

        expect(fn () => DB::table('renatio_dynamicpdf_pdf_layouts')->insert(['code' => 'acme::pdf.layouts.default', 'name' => 'Twin', 'content_html' => '<p></p>']))
            ->toThrow(Illuminate\Database\QueryException::class);
    });
});
