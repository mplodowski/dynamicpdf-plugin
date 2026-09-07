<?php

use Illuminate\Support\Facades\Artisan;
use Renatio\DynamicPDF\Classes\PDFManager;

describe('dynamicpdf:check', function () {
    afterEach(fn () => PDFManager::forgetInstance());

    it('passes with the default configuration', function () {
        expect(Artisan::call('dynamicpdf:check'))->toBe(0)
            ->and(Artisan::output())->toContain('PASS');
    });

    it('fails when the font directory cannot be created', function () {
        config(['dompdf.options.font_dir' => __FILE__ . '/fonts']);

        expect(Artisan::call('dynamicpdf:check'))->toBe(1)
            ->and(Artisan::output())->toContain('FAIL');
    });

    it('warns when inline PHP is enabled', function () {
        config(['dompdf.options.enable_php' => true]);

        Artisan::call('dynamicpdf:check');

        expect(Artisan::output())->toContain('WARN');
    });

    it('fails for a registered code without a view file', function () {
        PDFManager::forgetInstance();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.nowhere']);

        expect(Artisan::call('dynamicpdf:check'))->toBe(1)
            ->and(Artisan::output())->toContain('renatio.dynamicpdf::pdf.nowhere');
    });
});
