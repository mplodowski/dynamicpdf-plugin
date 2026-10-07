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
use System\Models\Parameter;

describe('SyncTemplates', function () {
    beforeEach(function () {
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
    });

    afterEach(function () {
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

    it('keeps and reports templates that are no longer registered, with their translations', function () {
        $this->enableTranslation('de');
        $orphan = $this->createTemplate(['code' => 'gone::pdf.stale', 'is_custom' => false]);
        $orphan->setTranslation('content_html', 'de', '<p>Übersetzt</p>');
        $orphan->save();
        $sync = new SyncTemplates;

        $sync->handle();

        expect(Template::whereCode('gone::pdf.stale')->value('id'))->toBe($orphan->id)
            ->and($this->findTemplate('gone::pdf.stale')->getTranslation('content_html', 'de', false))->toBe('<p>Übersetzt</p>')
            ->and($sync->report()['orphaned'])->toBe(['gone::pdf.stale'])
            ->and($sync->report()['deleted'])->toBe([]);
    });

    it('deletes unregistered templates only when pruning, also with nothing registered', function (bool $registered) {
        if (! $registered) {
            PDFManager::forgetInstance();
        }

        $this->createTemplate(['code' => 'gone::pdf.stale', 'is_custom' => false]);
        $this->createTemplate(['code' => 'kept::pdf.custom', 'is_custom' => true]);
        $sync = new SyncTemplates;

        $sync->handle(prune: true);

        expect(Template::whereCode('gone::pdf.stale')->exists())->toBeFalse()
            ->and(Template::whereCode('kept::pdf.custom')->exists())->toBeTrue()
            ->and($sync->report()['deleted'])->toBe(['gone::pdf.stale']);
    })->with(['other views registered' => true, 'nothing registered' => false]);

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

    it('keeps the stored layout when the view resolves its layout code to nothing', function () {
        $layout = $this->createLayout(['code' => 'syncviews::pdf.layouts.kept']);
        $this->views = $this->registerViewTemplates('syncviews', ['a' => "title = \"Second\"\nlayout = \"syncviews::pdf.layouts.unknown\"\n==\n<p>v2</p>"]);
        $this->createTemplate(['code' => 'syncviews::pdf.a', 'is_custom' => false, 'title' => 'First', 'content_html' => '<p>v1</p>', 'layout_id' => $layout->id]);

        $sync = new SyncTemplates;
        $sync->handle();

        $row = DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'syncviews::pdf.a')->first();

        expect((int) $row->layout_id)->toBe($layout->id)
            ->and($sync->report()['updated'])->toBe([]);
    });

    it('leaves a customised template alone when its view file changes', function () {
        $this->views = $this->registerViewTemplates('syncviews', ['a' => "title = \"Second\"\n==\n<p>v2</p>"]);
        $this->createTemplate(['code' => 'syncviews::pdf.a', 'is_custom' => true, 'title' => 'Mine', 'content_html' => '<p>mine</p>']);

        (new SyncTemplates)->handle();

        expect(DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'syncviews::pdf.a')->value('title'))->toBe('Mine');
    });

    it('writes a changed view file back to the row of a locked layout', function () {
        $this->views = $this->registerViewLayouts('syncviews', ['layouts/a' => "name = \"First\"\n==\np { color: red; }\n==\n<p>v1</p>"]);

        $sync = new SyncTemplates;
        $sync->handle();

        File::put($this->views . '/pdf/layouts/a.htm', "name = \"Second\"\n==\np { color: blue; }\n==\n<p>v2</p>");

        $sync->handle();

        $row = DB::table('renatio_dynamicpdf_pdf_layouts')->where('code', 'syncviews::pdf.layouts.a')->first();

        expect($row)
            ->name->toBe('Second')
            ->content_css->toBe('p { color: blue; }')
            ->content_html->toBe('<p>v2</p>')
            ->is_locked->toBeTruthy()
            ->and($sync->report()['updated'])->toBe(['syncviews::pdf.layouts.a']);
    });

    it('leaves a locked layout on its stored content until the migration has flagged layouts to follow their view', function () {
        expect(Parameter::get(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER))->toBeTruthy();

        Parameter::set(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER, 0);
        $this->views = $this->registerViewLayouts('syncviews', ['layouts/a' => "name = \"Second\"\n==\n<p>v2</p>"]);
        $this->createLayout(['code' => 'syncviews::pdf.layouts.a', 'name' => 'First', 'content_html' => '<p>v1</p>', 'is_locked' => true]);

        $sync = new SyncTemplates;
        $sync->handle();

        expect(DB::table('renatio_dynamicpdf_pdf_layouts')->where('code', 'syncviews::pdf.layouts.a')->value('content_html'))->toBe('<p>v1</p>')
            ->and(Layout::byCode('syncviews::pdf.layouts.a')->content_html)->toBe('<p>v1</p>')
            ->and($sync->report()['updated'])->toBe([]);

        Parameter::set(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER, 1);
        $sync->handle();

        expect(DB::table('renatio_dynamicpdf_pdf_layouts')->where('code', 'syncviews::pdf.layouts.a')->value('content_html'))->toBe('<p>v2</p>')
            ->and(Layout::byCode('syncviews::pdf.layouts.a')->content_html)->toBe('<p>v2</p>');
    });

    it('never refreshes an edited layout from its view', function () {
        $this->views = $this->registerViewLayouts('syncviews', ['layouts/a' => "name = \"Second\"\n==\n<p>v2</p>"]);
        $this->createLayout(['code' => 'syncviews::pdf.layouts.a', 'name' => 'Mine', 'content_html' => '<p>mine</p>', 'is_locked' => false]);

        $sync = new SyncTemplates;
        $sync->handle();

        expect(DB::table('renatio_dynamicpdf_pdf_layouts')->where('code', 'syncviews::pdf.layouts.a')->value('content_html'))->toBe('<p>mine</p>')
            ->and(Layout::byCode('syncviews::pdf.layouts.a')->content_html)->toBe('<p>mine</p>')
            ->and($sync->report()['updated'])->toBe([]);
    });

    it('synchronises before a templates page is displayed', function () {
        (new Templates)->beforeDisplay();

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue();
    });
});
