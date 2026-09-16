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

    it('warns without creating it when the font directory is missing but its parent is writable', function () {
        $missing = sys_get_temp_dir() . '/dynamicpdf-missing-' . uniqid();
        config(['dompdf.options.font_dir' => $missing]);

        expect(Artisan::call('dynamicpdf:check'))->toBe(0)
            ->and(Artisan::output())->toContain('will be created on the first render')
            ->and($missing)->not->toBeDirectory();
    });

    it('fails when the missing font directory cannot be created', function () {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root can write into an unwritable directory');
        }

        $parent = sys_get_temp_dir() . '/dynamicpdf-readonly-' . uniqid();
        mkdir($parent, 0555);

        try {
            config(['dompdf.options.font_dir' => $parent . '/fonts']);

            expect(Artisan::call('dynamicpdf:check'))->toBe(1)
                ->and(Artisan::output())->toContain('FAIL');
        } finally {
            chmod($parent, 0755);
            rmdir($parent);
        }
    });

    it('fails when the temporary directory is missing', function () {
        config(['dompdf.options.temp_dir' => sys_get_temp_dir() . '/dynamicpdf-missing-' . uniqid()]);

        expect(Artisan::call('dynamicpdf:check'))->toBe(1)
            ->and(Artisan::output())->toContain('FAIL');
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
