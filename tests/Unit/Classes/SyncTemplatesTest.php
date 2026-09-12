<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
        SyncTemplates::forgetFailures();
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
    });

    afterEach(function () {
        PDFManager::forgetInstance();
        SyncTemplates::forgetFailures();

        if (isset($this->views)) {
            File::deleteDirectory($this->views);
        }
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

    it('writes a changed view file back to the row of a non-customised template', function () {
        $this->views = $this->registerViewTemplates('syncviews', ['a' => "title = \"First\"\n==\n<p>v1</p>"]);

        $sync = new SyncTemplates;
        $sync->handle();

        File::put($this->views . '/pdf/a.htm', "title = \"Second\"\n==\n<p>v2</p>");

        $sync->handle();

        $row = DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'syncviews::pdf.a')->first();

        expect($row->title)->toBe('Second')
            ->and($row->content_html)->toBe('<p>v2</p>')
            ->and($sync->report()['updated'])->toBe(['syncviews::pdf.a']);
    });

    it('keeps the stored row when the view of its layout cannot be read', function () {
        PDFManager::instance()->registerLayouts(['syncviews::pdf.layouts.gone']);
        $this->views = $this->registerViewTemplates('syncviews', ['a' => "title = \"Second\"\nlayout = \"syncviews::pdf.layouts.gone\"\n==\n<p>v2</p>"]);
        $this->createTemplate(['code' => 'syncviews::pdf.a', 'is_custom' => false, 'title' => 'First', 'content_html' => '<p>v1</p>']);
        Log::spy();

        $sync = new SyncTemplates;
        $sync->handle();

        $row = DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'syncviews::pdf.a')->first();

        expect($row->title)->toBe('First')
            ->and($row->content_html)->toBe('<p>v1</p>')
            ->and($sync->report()['updated'])->toBe([]);
    });

    it('keeps the stored row when the view file parses to no content', function () {
        $this->views = $this->registerViewTemplates('syncviews', ['a' => '']);
        $this->createTemplate(['code' => 'syncviews::pdf.a', 'is_custom' => false, 'title' => 'First', 'content_html' => '<p>v1</p>']);

        $sync = new SyncTemplates;
        $sync->handle();

        $row = DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'syncviews::pdf.a')->first();

        expect($row->title)->toBe('First')
            ->and($row->content_html)->toBe('<p>v1</p>')
            ->and($sync->report()['updated'])->toBe([]);
    });

    it('leaves a customised template alone when its view file changes', function () {
        $this->views = $this->registerViewTemplates('syncviews', ['a' => "title = \"Second\"\n==\n<p>v2</p>"]);
        $this->createTemplate(['code' => 'syncviews::pdf.a', 'is_custom' => true, 'title' => 'Mine', 'content_html' => '<p>mine</p>']);

        (new SyncTemplates)->handle();

        expect(DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'syncviews::pdf.a')->value('title'))->toBe('Mine');
    });

    it('synchronises before a templates page is displayed', function () {
        (new Templates)->beforeDisplay();

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue();
    });
});
