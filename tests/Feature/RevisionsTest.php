<?php

use Backend\Facades\Backend;
use Illuminate\Support\Facades\DB;
use October\Rain\Database\Model;
use October\Rain\Exception\ForbiddenException;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Revision;

describe('Revisions', function () {
    afterEach(fn () => Model::unguard());

    it('keeps the previous content with its author when a backend save changes it', function () {
        $user = actingAsPdfManager();
        $template = $this->createTemplate(['title' => 'Invoice', 'content_html' => '<p>Old</p>']);
        $layout = $this->createLayout(['name' => 'Plain', 'content_css' => 'p { color: red; }']);

        Model::reguard();
        $this->saveTemplateForm($template->id, ['title' => 'Invoice', 'code' => $template->code, 'content_html' => '<p>New</p>'])->assertOk();
        $this->saveLayoutForm($layout->id, ['name' => 'Plain', 'code' => $layout->code, 'content_html' => (string) $layout->content_html, 'content_css' => 'p { color: blue; }'])->assertOk();

        $revisions = Revision::query()->orderBy('id')->get();

        expect(DB::table('renatio_dynamicpdf_revisions')->orderBy('id')->get(['revisionable_id', 'user_id', 'user_name'])->map(fn (object $row) => (array) $row)->all())
            ->toBe([
                ['revisionable_id' => $template->id, 'user_id' => $user->id, 'user_name' => 'Permission Tests'],
                ['revisionable_id' => $layout->id, 'user_id' => $user->id, 'user_name' => 'Permission Tests'],
            ])
            ->and($revisions->first()?->data['content_html'])->toBe('<p>Old</p>')
            ->and($revisions->last()?->data['content_css'])->toBe('p { color: red; }');
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
        $template = $this->createTemplate(['content_html' => '<p>current</p>']);

        foreach (range(1, Revision::LIMIT + 2) as $version) {
            $template->recordRevision(['content_html' => "<p>v{$version}</p>"] + $template->revisionSnapshot());
        }

        $kept = Revision::query()->orderBy('id')->get()->map(fn ($revision) => $revision->data['content_html']);

        expect($kept)->toHaveCount(Revision::LIMIT)
            ->and($kept->first())->toBe('<p>v3</p>')
            ->and($kept->last())->toBe('<p>v' . (Revision::LIMIT + 2) . '</p>');
    });

    it('lists the versions for an administrator whose preferences have no timezone', function () {
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

        $this->get(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id))->assertOk()->assertSee('onRestoreRevision');
    });

    it('restores a version, marks the record customised and keeps the replaced version', function () {
        actingAsPdfManager();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);
        $current = $this->findTemplate('renatio.dynamicpdf::pdf.invoice')->revisionSnapshot();
        $template->recordRevision(['title' => 'Old title', 'content_html' => '<p>Old</p>', 'size' => 'a5'] + $current);

        Model::reguard();
        $this->post(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id), ['revision' => Revision::query()->sole()->id], [
            'X-AJAX-HANDLER' => 'onRestoreRevision',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        $restored = $this->findTemplate('renatio.dynamicpdf::pdf.invoice');

        expect($restored->is_custom)->toBeTrue()
            ->and($restored->title)->toBe('Old title')
            ->and($restored->content_html)->toBe('<p>Old</p>')
            ->and($restored->size)->toBe('a5')
            ->and(Revision::query()->orderByDesc('id')->first()?->data['content_html'])->toBe($current['content_html']);
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
