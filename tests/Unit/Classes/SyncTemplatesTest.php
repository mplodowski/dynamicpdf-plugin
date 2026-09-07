<?php

use Illuminate\Support\Facades\Log;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Plugin;

describe('SyncTemplates', function () {
    beforeEach(function () {
        PDFManager::forgetInstance();
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
    });

    afterEach(function () {
        PDFManager::forgetInstance();
        SyncTemplates::forgetFailures();
    });

    it('creates registered layouts and templates from their views', function () {
        (new SyncTemplates)->handle();

        expect(Layout::byCode('renatio.dynamicpdf::pdf.layouts.default')->is_locked)->toBeTrue()
            ->and(Template::byCode('renatio.dynamicpdf::pdf.invoice')->is_custom)->toBeFalse()
            ->and(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue();
    });

    it('removes stored templates that are no longer registered unless customised', function () {
        $this->createTemplate(['code' => 'gone::pdf.stale', 'is_custom' => false]);
        $this->createTemplate(['code' => 'kept::pdf.custom', 'is_custom' => true]);

        (new SyncTemplates)->handle();

        expect(Template::whereCode('gone::pdf.stale')->exists())->toBeFalse()
            ->and(Template::whereCode('kept::pdf.custom')->exists())->toBeTrue();
    });

    it('skips a registered code without a view file, logs it and still creates the others', function () {
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.missing']);
        $log = Log::spy();

        (new SyncTemplates)->handle();

        $log->shouldHaveReceived('error')->once()->withArgs(fn (string $message): bool => str_contains($message, 'pdf.layouts.missing'));

        expect(Layout::whereCode('renatio.dynamicpdf::pdf.layouts.missing')->exists())->toBeFalse()
            ->and(Layout::whereCode('renatio.dynamicpdf::pdf.layouts.default')->exists())->toBeTrue()
            ->and(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue();
    });

    it('does not synchronise on plugin boot or controller construction', function () {
        (new Plugin(app()))->boot();
        new Templates;

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeFalse();
    });

    it('keeps a stored template whose view file went missing', function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.gone']);
        $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.gone', 'is_custom' => false, 'content_html' => '<p>stored</p>']);
        Log::spy();

        expect(Template::byCode('renatio.dynamicpdf::pdf.gone')->content_html)->toBe('<p>stored</p>');
    });

    it('synchronises before a templates page is displayed', function () {
        (new Templates)->beforeDisplay();

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue();
    });
});
