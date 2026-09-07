<?php

use Illuminate\Support\Facades\Artisan;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Template;

describe('dynamicpdf:sync', function () {
    afterEach(fn () => PDFManager::forgetInstance());

    it('creates registered views and reports what it did', function () {
        PDFManager::forgetInstance();
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default', 'renatio.dynamicpdf::pdf.layouts.missing']);
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);

        $exitCode = Artisan::call('dynamicpdf:sync');
        $output = Artisan::output();

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue()
            ->and($output)->toContain('renatio.dynamicpdf::pdf.layouts.default')
            ->and($output)->toContain('renatio.dynamicpdf::pdf.invoice')
            ->and($output)->toContain('renatio.dynamicpdf::pdf.layouts.missing')
            ->and($exitCode)->toBe(1);
    });
});
