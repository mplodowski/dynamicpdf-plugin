<?php

use Backend\Facades\Backend;
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
            ->and($sync->orphanedTemplates())->toBe(['gone::pdf.stale'])
            ->and($sync->report()['deleted'])->toBe([]);
    });

    it('deletes unregistered templates only when pruning, also with nothing registered', function (bool $registered) {
        if (! $registered) {
            PDFManager::forgetInstance();
            expect(PDFManager::instance()->listRegisteredTemplates())->toBe([]);
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
        $log = Log::spy();

        expect(Template::byCode('renatio.dynamicpdf::pdf.gone')->content_html)->toBe('<p>stored</p>');

        $log->shouldHaveReceived('error')->once()->withArgs(fn (string $message): bool => str_contains($message, 'could not read the view of renatio.dynamicpdf::pdf.gone'));
    });

    it('keeps a stored layout whose view file went missing', function () {
        Parameter::set(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER, 1);
        Parameter::clearInternalCache();
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.gone']);
        $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.layouts.gone', 'is_locked' => true, 'content_html' => '<p>stored</p>']);
        $log = Log::spy();

        expect(Layout::byCode('renatio.dynamicpdf::pdf.layouts.gone')->content_html)->toBe('<p>stored</p>');

        $log->shouldHaveReceived('error')->once()->withArgs(fn (string $message): bool => str_contains($message, 'could not read the view of renatio.dynamicpdf::pdf.layouts.gone'));
    });

    it('writes a changed view file back to the row of a non-customised template', function () {
        $views = $this->registerViewTemplates('syncviews', ['a' => "title = \"First\"\n==\n<p>v1</p>"]);

        $sync = new SyncTemplates;
        $sync->handle();

        File::put($views . '/pdf/a.htm', "title = \"Second\"\n==\n<p>v2</p>");

        $sync->handle();

        $row = DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'syncviews::pdf.a')->first();

        expect($row->title)->toBe('Second')
            ->and($row->content_html)->toBe('<p>v2</p>')
            ->and($sync->report()['updated'])->toBe(['syncviews::pdf.a']);
    });

    it('keeps the stored row when the view of its layout cannot be read', function () {
        PDFManager::instance()->registerLayouts(['syncviews::pdf.layouts.gone']);
        $this->registerViewTemplates('syncviews', ['a' => "title = \"Second\"\nlayout = \"syncviews::pdf.layouts.gone\"\n==\n<p>v2</p>"]);
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
        $this->registerViewTemplates('syncviews', ['a' => '']);
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
        $this->registerViewTemplates('syncviews', ['a' => "title = \"Second\"\nlayout = \"syncviews::pdf.layouts.unknown\"\n==\n<p>v2</p>"]);
        $this->createTemplate(['code' => 'syncviews::pdf.a', 'is_custom' => false, 'title' => 'First', 'content_html' => '<p>v1</p>', 'layout_id' => $layout->id]);

        $sync = new SyncTemplates;
        $sync->handle();

        $row = DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'syncviews::pdf.a')->first();

        expect((int) $row->layout_id)->toBe($layout->id)
            ->and($sync->report()['updated'])->toBe([]);
    });

    it('leaves a customised template alone when its view file changes', function () {
        $this->registerViewTemplates('syncviews', ['a' => "title = \"Second\"\n==\n<p>v2</p>"]);
        $this->createTemplate(['code' => 'syncviews::pdf.a', 'is_custom' => true, 'title' => 'Mine', 'content_html' => '<p>mine</p>']);

        (new SyncTemplates)->handle();

        expect(DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'syncviews::pdf.a')->value('title'))->toBe('Mine');
    });

    it('writes a changed view file back to the row of a locked layout', function () {
        $views = $this->registerViewLayouts('syncviews', ['layouts/a' => "name = \"First\"\n==\np { color: red; }\n==\n<p>v1</p>"]);

        $sync = new SyncTemplates;
        $sync->handle();

        File::put($views . '/pdf/layouts/a.htm', "name = \"Second\"\n==\np { color: blue; }\n==\n<p>v2</p>");

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
        $this->registerViewLayouts('syncviews', ['layouts/a' => "name = \"Second\"\n==\n<p>v2</p>"]);
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
        $this->registerViewLayouts('syncviews', ['layouts/a' => "name = \"Second\"\n==\n<p>v2</p>"]);
        $this->createLayout(['code' => 'syncviews::pdf.layouts.a', 'name' => 'Mine', 'content_html' => '<p>mine</p>', 'is_locked' => false]);

        $sync = new SyncTemplates;
        $sync->handle();

        expect(DB::table('renatio_dynamicpdf_pdf_layouts')->where('code', 'syncviews::pdf.layouts.a')->value('content_html'))->toBe('<p>mine</p>')
            ->and(Layout::byCode('syncviews::pdf.layouts.a')->content_html)->toBe('<p>mine</p>')
            ->and($sync->report()['updated'])->toBe([]);
    });

    it('synchronises when a templates page is displayed but not before an AJAX handler', function () {
        actingAsPdfManager();

        $this->listAction('templates::onPaginate', ['page' => 1])->assertOk();
        $afterHandler = Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists();

        $this->get(Backend::url('renatio/dynamicpdf/templates'))->assertOk();

        expect($afterHandler)->toBeFalse()
            ->and(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue();
    });

    it('does not synchronise on a preview page', function (string $action) {
        $template = $this->createTemplate();
        actingAsPdfManager();

        $this->get(Backend::url("renatio/dynamicpdf/templates/{$action}/{$template->id}"))->assertOk();

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeFalse();
    })->with(['previewpdf', 'html', 'preview']);

    it('reports no update for a view with empty optional sections on the next sync', function () {
        $views = $this->registerViewLayouts('syncviews', ['layouts/a' => "name = \"First\"\n==\n\n==\n<p>v1</p>"]);
        $this->registerViewTemplates('syncviews', ['a' => "title = \"First\"\ndescription = \"\"\n==\n<p>v1</p>"], $views);
        $sync = new SyncTemplates;

        $sync->handle();
        $sync->handle();

        expect($sync->report()['updated'])->toBe([]);
    });

    it('logs a registered code whose view cannot be read once per process', function () {
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.missing']);
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.gone']);
        $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.gone', 'is_custom' => false]);
        actingAsPdfManager();
        $log = Log::spy();

        $this->get(Backend::url('renatio/dynamicpdf/templates'))->assertOk();
        $this->get(Backend::url('renatio/dynamicpdf/templates'))->assertOk();
        $this->get(Backend::url('renatio/dynamicpdf/templates'))->assertOk();

        $log->shouldHaveReceived('error')->withArgs(fn (string $message): bool => str_contains($message, 'pdf.layouts.missing'))->once();
        $log->shouldHaveReceived('error')->withArgs(fn (string $message): bool => str_contains($message, 'pdf.gone'))->once();
    });

    it('stores empty form fields of a view-driven record as null so the next sync reports no update', function (string $kind) {
        if ($kind === 'template') {
            $this->registerViewTemplates('syncviews', ['a' => "title = \"First\"\n==\n<p>v1</p>"]);
        } else {
            $this->registerViewLayouts('syncviews', ['layouts/a' => "name = \"First\"\n==\n<p>v1</p>"]);
        }

        (new SyncTemplates)->handle();
        actingAsPdfManager();

        if ($kind === 'template') {
            $this->saveTemplateForm($this->findTemplate('syncviews::pdf.a')->id, [
                'title' => 'First',
                'description' => '',
                'content_html' => '<p>v1</p>',
                'size' => '',
                'orientation' => '',
                'sample_data' => '{"order": 1}',
            ])->assertOk();
        } else {
            $this->saveLayoutForm($this->findLayout('syncviews::pdf.layouts.a')->id, ['name' => 'First', 'content_html' => '<p>v1</p>', 'content_css' => ''])->assertOk();
        }

        $sync = new SyncTemplates;
        $sync->handle();

        expect($sync->report()['updated'])->toBe([]);
    })->with(['template', 'layout']);
});
