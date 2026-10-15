<?php

use Backend\Facades\Backend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use October\Rain\Database\Model;
use October\Rain\Exception\ForbiddenException;
use October\Rain\Support\Facades\Flash;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\RevisionHistory;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Revision;
use System\Models\File;

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

    it('records nothing for a save without changes or a view sync', function () {
        actingAsPdfManager();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        $template = $this->createTemplate(['title' => 'Invoice', 'content_html' => "<p>Same</p>\n<p>Lines</p>"]);
        $viewTemplate = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);

        $this->saveTemplateForm($template->id, ['title' => 'Invoice', 'code' => $template->code, 'content_html' => "<p>Same</p>\r\n<p>Lines</p>"])->assertOk();
        DB::table('renatio_dynamicpdf_pdf_templates')->where('id', $viewTemplate->id)->update(['content_html' => '<p>stale</p>']);
        (new SyncTemplates)->handle();

        expect(Revision::query()->count())->toBe(0);
    });

    it('records a reset to the view as a version of the resetting administrator', function () {
        $user = actingAsPdfManager();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'content_html' => '<p>edited</p>', 'is_custom' => true]);

        (new Templates)->update_onResetDefault($template->id);

        [$baseline, $reset] = Revision::query()->orderBy('id')->get()->whereInstanceOf(Revision::class)->values()->all() + [null, null];

        expect(Revision::query()->count())->toBe(2)
            ->and($baseline?->data['content_html'])->toBe('<p>edited</p>')
            ->and($reset?->data['content_html'])->toContain('Invoice')
            ->and($reset?->event())->toBe(Revision::EVENT_RESET)
            ->and($reset?->user_id)->toBe($user->id);
    });

    it('reloads the form when a save detaches the record from its view, so the notice and Reset to Default update', function () {
        actingAsPdfManager();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);

        Model::reguard();
        $response = $this->post(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id), [
            'Template' => ['title' => 'Edited', 'content_html' => '<p>Edited</p>'],
            'redirect' => '0',
        ], ['X-AJAX-HANDLER' => 'onSave', 'X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        expect(collect($response->json('__ajax.ops'))->pluck('op')->all())->toContain('redirect');
    });

    it('refreshes the history after a save that stays on the page', function () {
        actingAsPdfManager();
        $template = $this->createTemplate(['content_html' => '<p>Old</p>']);

        Model::reguard();
        $response = $this->post(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id), [
            'Template' => ['title' => 'Test Template', 'code' => $template->code, 'content_html' => '<p>New</p>'],
            'redirect' => '0',
        ], ['X-AJAX-HANDLER' => 'onSave', 'X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $history = collect($response->json('__ajax.ops'))->firstWhere('selector', '#dynamicpdf-revisions')['html'] ?? '';

        expect($history)->toContain('data-request="onRestoreRevision"')
            ->and($history)->toContain('revision-current');
    });

    it('records uploading, replacing and removing the background image of a layout, which Restore leaves alone', function () {
        actingAsPdfManager();
        $layout = $this->createLayout();
        $upload = function (string $name) use ($layout): File {
            $image = new File;
            $image->fromData(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), $name);
            $layout->background_img()->add($image);

            return $image;
        };

        $upload('first.png');
        $layout->background_img()->remove($upload('second.png'));

        $images = Revision::query()->orderBy('id')->get()->map(fn ($revision) => array_key_exists('background_img', $revision->data) ? $revision->data['background_img'] : 'missing')->all();
        $html = (string) $this->get(Backend::url('renatio/dynamicpdf/layouts/update/' . $layout->id))->assertOk()->getContent();

        expect($images)->toBe([null, 'first.png', 'second.png', null])
            ->and($html)->toContain('<span class="revision-old">second.png</span>');

        $layout->restoreRevision(Revision::query()->orderBy('id')->get()->whereInstanceOf(Revision::class)->values()->get(1) ?? throw new RuntimeException);

        expect($layout->background_img()->getQuery()->exists())->toBeFalse();
    });

    it('records a background image removed in the form together with the save, as one version after a baseline from before', function () {
        actingAsPdfManager();
        $layout = $this->createLayout(['name' => 'Plain']);
        $image = new File;
        $image->fromData(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), 'first.png');
        $layout->background_img()->add($image);
        Revision::query()->delete();
        DB::table('renatio_dynamicpdf_pdf_layouts')->where('id', $layout->id)->update(['updated_at' => '2026-01-02 03:04:05']);
        $layout->background_img()->remove($image, 'form-session');

        Model::reguard();
        $this->post(Backend::url('renatio/dynamicpdf/layouts/update/' . $layout->id), [
            'Layout' => ['name' => 'Renamed', 'code' => $layout->code, 'content_html' => (string) $layout->content_html],
            '_session_key' => 'form-session',
        ], ['X-AJAX-HANDLER' => 'onSave', 'X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $revisions = Revision::query()->orderBy('id')->get();

        expect($revisions->map(fn ($revision) => [$revision->data['name'], $revision->data['background_img']])->all())
            ->toBe([['Plain', 'first.png'], ['Renamed', null]])
            ->and((string) $revisions->first()?->created_at)->toBe('2026-01-02 03:04:05');
    });

    it('does not list the background image as changed against a version saved before it was versioned', function () {
        $layout = $this->createLayout(['name' => 'Renamed']);
        $image = new File;
        $image->fromData(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), 'logo.png');
        $layout->background_img()->add($image);
        Revision::query()->delete();

        $layout->recordRevision(array_diff_key(['name' => 'Plain'] + $layout->revisionSnapshot(), ['background_img' => true]));

        expect(array_column((new RevisionHistory($layout, []))->rows()[0]['changes'] ?? [], 'label'))->toBe(['Name']);
    });

    it('neither offers nor reports a restore of a version that differs only in the background image', function () {
        actingAsPdfManager();
        $layout = $this->createLayout();
        $image = new File;
        $image->fromData(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), 'logo.png');
        $layout->background_img()->add($image);

        $html = (string) $this->get(Backend::url('renatio/dynamicpdf/layouts/update/' . $layout->id))->assertOk()->getContent();

        Model::reguard();
        $this->post(Backend::url('renatio/dynamicpdf/layouts/update/' . $layout->id), ['revision' => Revision::query()->orderBy('id')->value('id')], [
            'X-AJAX-HANDLER' => 'onRestoreRevision',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        expect(Revision::query()->count())->toBe(2)
            ->and($html)->not->toContain('data-request="onRestoreRevision"')
            ->and(Flash::messages())->toBe([]);
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

    it('lists what each version changed and offers Restore for all but the current one, also without a timezone preference', function () {
        $user = actingAsPdfManager();
        DB::table('backend_user_preferences')->insert([
            'user_id' => $user->id,
            'namespace' => 'backend',
            'group' => 'backend',
            'item' => 'preferences',
            'value' => '{"locale":"en"}',
        ]);
        $layout = $this->createLayout(['name' => 'Letterhead <b>']);
        $template = $this->createTemplate(['layout_id' => $layout->id, 'content_html' => '<p>New</p>']);
        $template->recordRevision(['title' => 'Old <title>', 'layout_id' => null, 'content_html' => '<p>Old</p>'] + $template->revisionSnapshot());

        $html = (string) $this->get(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id))->assertOk()->getContent();
        $rows = explode('<tr>', substr($html, (int) strpos($html, 'dynamicpdf-revisions')));

        expect($rows)->toHaveCount(4)
            ->and($rows[2])->toContain('<span class="revision-old">Old &lt;title&gt;</span>')
            ->and($rows[2])->toContain('<span class="revision-new">Test Template</span>')
            ->and($rows[2])->toContain('<span class="revision-new">Letterhead &lt;b&gt;</span>')
            ->and($rows[2])->toContain('<span class="revision-field">HTML</span>')
            ->and($rows[2])->toContain('revision-current')
            ->and($rows[2])->not->toContain('onRestoreRevision')
            ->and($rows[3])->toContain('State before the first change')
            ->and($rows[3])->toContain('data-request="onRestoreRevision"')
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
