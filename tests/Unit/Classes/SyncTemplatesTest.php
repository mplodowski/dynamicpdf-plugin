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

    afterEach(fn () => PDFManager::forgetInstance());

    it('creates registered layouts and templates from their views', function () {
        (new SyncTemplates)->handle();

        expect((bool) Layout::whereCode('renatio.dynamicpdf::pdf.layouts.default')->first()?->is_locked)->toBeTrue()
            ->and((bool) Template::whereCode('renatio.dynamicpdf::pdf.invoice')->first()?->is_custom)->toBeFalse()
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
        Log::shouldReceive('error')->once()->withArgs(fn (string $message): bool => str_contains($message, 'pdf.layouts.missing'));

        (new SyncTemplates)->handle();

        expect(Layout::whereCode('renatio.dynamicpdf::pdf.layouts.missing')->exists())->toBeFalse()
            ->and(Layout::whereCode('renatio.dynamicpdf::pdf.layouts.default')->exists())->toBeTrue()
            ->and(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue();
    });

    it('does not synchronise on plugin boot', function () {
        (new Plugin(app()))->boot();

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeFalse();
    });

    it('synchronises when the templates controller is opened', function () {
        new Templates;

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue();
    });
});
