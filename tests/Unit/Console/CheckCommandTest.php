<?php

use Illuminate\Support\Facades\Artisan;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('dynamicpdf:check', function () {
    it('passes with the default configuration', function () {
        expect(Artisan::call('dynamicpdf:check'))->toBe(0)
            ->and(Artisan::output())->toContain('PASS');
    });

    it('warns without creating it when the font directory is missing but its parent is writable', function () {
        $missing = sys_get_temp_dir() . '/dynamicpdf-missing-' . uniqid();
        config(['dompdf.options.font_dir' => $missing]);

        expect(Artisan::call('dynamicpdf:check'))->toBe(0)
            ->and(Artisan::output())->toContain('the first render creates it')
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

        expect(Artisan::output())->toContain('enabled: templates can run PHP on the server');
    });

    it('fails for a registered code without a view file', function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.nowhere']);

        expect(Artisan::call('dynamicpdf:check'))->toBe(1)
            ->and(Artisan::output())->toContain('renatio.dynamicpdf::pdf.nowhere');
    });

    it('lists customized records whose content matches their view apart from whitespace', function () {
        $this->registerViewLayouts('checkspaces', ['layouts/letter' => "name = \"Letter\"\n==\n<div>\n<p>Letter</p>\n</div>"]);
        $this->registerViewTemplates('checkspaces', [
            'same' => "title = \"Same\"\n==\n<p>Same</p>",
            'edited' => "title = \"Edited\"\n==\n<p>Edited</p>",
        ]);
        (new SyncTemplates)->handle();
        Layout::whereCode('checkspaces::pdf.layouts.letter')->update(['is_custom' => true, 'content_html' => "<div>    <p>Letter</p> </div>\n"]);
        Template::whereCode('checkspaces::pdf.same')->update(['is_custom' => true, 'content_html' => "\n  <p>Same</p>  "]);
        Template::whereCode('checkspaces::pdf.edited')->update(['is_custom' => true, 'content_html' => '<p>Changed</p>']);

        Artisan::call('dynamicpdf:check');

        expect(Artisan::output())->toContain('checkspaces::pdf.layouts.letter')
            ->toContain('checkspaces::pdf.same')
            ->not->toContain('checkspaces::pdf.edited');
    });
});
