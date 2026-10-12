<?php

use Backend\Facades\Backend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use October\Rain\Database\Model;
use October\Rain\Exception\ForbiddenException;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Revision;

describe('Revisions', function () {
    afterEach(fn () => Model::unguard());

    it('records each saved version with its author after a baseline of the content from before', function () {
        $user = actingAsPdfManager();
        $template = $this->createTemplate(['title' => 'Invoice', 'content_html' => '<p>Old</p>']);
        $layout = $this->createLayout(['name' => 'Plain', 'content_css' => 'p { color: red; }']);
        DB::table('renatio_dynamicpdf_pdf_templates')->where('id', $template->id)->update(['updated_at' => '2026-01-02 03:04:05']);

        Model::reguard();
        $this->saveTemplateForm($template->id, ['title' => 'Invoice', 'code' => $template->code, 'content_html' => '<p>New</p>'])->assertOk();
        $this->saveTemplateForm($template->id, ['title' => 'Invoice', 'code' => $template->code, 'content_html' => '<p>Newer</p>'])->assertOk();
        $this->saveLayoutForm($layout->id, ['name' => 'Plain', 'code' => $layout->code, 'content_html' => (string) $layout->content_html, 'content_css' => 'p { color: blue; }'])->assertOk();

        $revisions = Revision::query()->orderBy('id')->get();

        expect(DB::table('renatio_dynamicpdf_revisions')->orderBy('id')->get(['revisionable_id', 'user_id', 'user_name'])->map(fn (object $row) => (array) $row)->all())
            ->toBe([
                ['revisionable_id' => $template->id, 'user_id' => null, 'user_name' => null],
                ['revisionable_id' => $template->id, 'user_id' => $user->id, 'user_name' => 'Permission Tests'],
                ['revisionable_id' => $template->id, 'user_id' => $user->id, 'user_name' => 'Permission Tests'],
                ['revisionable_id' => $layout->id, 'user_id' => null, 'user_name' => null],
                ['revisionable_id' => $layout->id, 'user_id' => $user->id, 'user_name' => 'Permission Tests'],
            ])
            ->and($revisions->map(fn ($revision) => $revision->data['content_html'])->take(3)->all())->toBe(['<p>Old</p>', '<p>New</p>', '<p>Newer</p>'])
            ->and((string) $revisions->first()?->created_at)->toBe('2026-01-02 03:04:05')
            ->and($revisions->last()?->data['content_css'])->toBe('p { color: blue; }');
    });

    it('keeps the save when the version cannot be recorded', function () {
        actingAsPdfManager();
        $log = Log::spy();
        $template = $this->createTemplate(['content_html' => '<p>Old</p>']);
        Revision::creating(fn () => throw new RuntimeException('Disk full'));

        $this->saveTemplateForm($template->id, ['title' => 'Test Template', 'code' => $template->code, 'content_html' => '<p>New</p>'])->assertOk();

        expect($this->findTemplate($template->code)->content_html)->toBe('<p>New</p>')
            ->and(Revision::query()->count())->toBe(0);
        $log->shouldHaveReceived('warning')->once();
    });

    it('records nothing for a save without changes, a reset to the view or a view sync', function () {
        actingAsPdfManager();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        $template = $this->createTemplate(['title' => 'Invoice', 'content_html' => "<p>Same</p>\n<p>Lines</p>"]);
        $viewTemplate = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'content_html' => '<p>edited</p>', 'is_custom' => true]);

        $this->saveTemplateForm($template->id, ['title' => 'Invoice', 'code' => $template->code, 'content_html' => "<p>Same</p>\r\n<p>Lines</p>"])->assertOk();
        (new Templates)->update_onResetDefault($viewTemplate->id);
        DB::table('renatio_dynamicpdf_pdf_templates')->where('id', $viewTemplate->id)->update(['content_html' => '<p>stale</p>']);
        (new SyncTemplates)->handle();

        expect(Revision::query()->count())->toBe(0);
    });

    it('keeps only the newest versions of a record', function () {
        $template = $this->createTemplate(['content_html' => '<p>v0</p>']);

        foreach (range(1, Revision::LIMIT + 1) as $version) {
            $previous = $template->revisionSnapshot();
            $template->content_html = "<p>v{$version}</p>";
            $template->save();
            $template->recordRevision($previous);
        }

        $kept = Revision::query()->orderBy('id')->get()->map(fn ($revision) => $revision->data['content_html']);

        expect($kept)->toHaveCount(Revision::LIMIT)
            ->and($kept->first())->toBe('<p>v2</p>')
            ->and($kept->last())->toBe('<p>v' . (Revision::LIMIT + 1) . '</p>');
    });

    it('offers Restore for every version but the current one, also without a timezone preference', function () {
        $user = actingAsPdfManager();
        DB::table('backend_user_preferences')->insert([
            'user_id' => $user->id,
            'namespace' => 'backend',
            'group' => 'backend',
            'item' => 'preferences',
            'value' => '{"locale":"en"}',
        ]);
        $template = $this->createTemplate();
        $template->recordRevision(['title' => 'Old title'] + $template->revisionSnapshot());

        $html = (string) $this->get(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id))->assertOk()->getContent();

        expect(Revision::query()->count())->toBe(2)
            ->and(substr_count($html, 'data-request="onRestoreRevision"'))->toBe(1);
    });

    it('restores a version as a new one by the restoring administrator and marks the record customised', function () {
        $user = actingAsPdfManager();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);
        $template->recordRevision(['title' => 'Old title', 'content_html' => '<p>Old</p>', 'size' => 'a5'] + $this->findTemplate('renatio.dynamicpdf::pdf.invoice')->revisionSnapshot());

        Model::reguard();
        $this->post(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id), ['revision' => Revision::query()->orderBy('id')->first()?->id], [
            'X-AJAX-HANDLER' => 'onRestoreRevision',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        $restored = $this->findTemplate('renatio.dynamicpdf::pdf.invoice');
        $latest = Revision::query()->orderByDesc('id')->first();

        expect($restored->is_custom)->toBeTrue()
            ->and($restored->title)->toBe('Old title')
            ->and($restored->content_html)->toBe('<p>Old</p>')
            ->and($restored->size)->toBe('a5')
            ->and(Revision::query()->count())->toBe(3)
            ->and($latest?->data['content_html'])->toBe('<p>Old</p>')
            ->and(DB::table('renatio_dynamicpdf_revisions')->orderByDesc('id')->value('user_id'))->toBe($user->id);
    });

    it('leaves the record alone when the version equals its content', function () {
        actingAsPdfManager();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);
        $template = $this->findTemplate('renatio.dynamicpdf::pdf.invoice');
        $template->recordRevision(['title' => 'Old title'] + $template->revisionSnapshot());

        Model::reguard();
        $this->post(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id), ['revision' => Revision::query()->orderByDesc('id')->first()?->id], [
            'X-AJAX-HANDLER' => 'onRestoreRevision',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        expect($this->findTemplate('renatio.dynamicpdf::pdf.invoice')->is_custom)->toBeFalse()
            ->and(Revision::query()->count())->toBe(2);
    });

    it('refuses to restore without the update permission', function () {
        actingAsPdfUserWith(['manage_templates', 'manage_templates.preview']);
        $template = $this->createTemplate(['content_html' => '<p>current</p>']);
        $template->recordRevision(['content_html' => '<p>Old</p>'] + $template->revisionSnapshot());

        expect(fn () => (new Templates)->update_onRestoreRevision($template->id))->toThrow(ForbiddenException::class);
    });

    it('deletes the versions with the record', function () {
        $template = $this->createTemplate();
        $template->recordRevision(['content_html' => '<p>Old</p>'] + $template->revisionSnapshot());

        $template->delete();

        expect(Revision::query()->count())->toBe(0);
    });
});
