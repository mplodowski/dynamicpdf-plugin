<?php

use Illuminate\Support\Facades\Artisan;
use Renatio\DynamicPDF\Classes\PDFManager;

describe('dynamicpdf:check', function () {
    beforeEach(fn () => PDFManager::forgetInstance());

    afterEach(fn () => PDFManager::forgetInstance());

    it('passes with the default configuration', function () {
        expect(Artisan::call('dynamicpdf:check'))->toBe(0)
            ->and(Artisan::output())->toContain('PASS');
    });

    it('fails when the font directory is missing without creating it', function () {
        $missing = sys_get_temp_dir() . '/dynamicpdf-missing-' . uniqid();
        config(['dompdf.options.font_dir' => $missing]);

        expect(Artisan::call('dynamicpdf:check'))->toBe(1)
            ->and(Artisan::output())->toContain('FAIL')
            ->and($missing)->not->toBeDirectory();
    });

    it('warns when allowed_remote_hosts is not an array', function () {
        config(['dompdf.options.enable_remote' => true, 'dompdf.options.allowed_remote_hosts' => 'cdn.example.com']);

        Artisan::call('dynamicpdf:check');

        expect(Artisan::output())->toContain('must be an array');
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
