<?php

use Backend\Facades\Backend;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use October\Rain\Exception\ForbiddenException;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Tests\TestCase;

describe('Permissions', function () {
    it('refuses the template preview without manage_templates', function () {
        actingAsBackendUserWith([]);
        $template = $this->createTemplate();

        expect(fn () => (new Templates)->run('html', [$template->id]))->toThrow(ForbiddenException::class);
    });

    it('refuses the layout preview without manage_layouts', function () {
        actingAsBackendUserWith(['manage_templates']);
        $layout = $this->createLayout();

        expect(fn () => (new Layouts)->run('html', [$layout->id]))->toThrow(ForbiddenException::class);
    });

    it('hides the layouts list without manage_layouts', function () {
        actingAsBackendUserWith(['manage_templates', 'manage_templates.create']);
        $this->createLayout();

        $content = (new Templates)->run('index', ['layouts'])->getContent();

        expect($content)->toContain('renatio/dynamicpdf/templates/create')
            ->and($content)->not->toContain('renatio/dynamicpdf/templates/index/layouts')
            ->and($content)->not->toContain(trans('renatio.dynamicpdf::lang.templates.new_layout'));
    });

    it('shows the layouts list with manage_layouts', function () {
        actingAsBackendUserWith(['manage_templates', 'manage_layouts', 'manage_layouts.create']);
        $this->createLayout();

        $content = (new Templates)->run('index', ['layouts'])->getContent();

        expect($content)->toContain('renatio/dynamicpdf/templates/index/layouts')
            ->and($content)->toContain(trans('renatio.dynamicpdf::lang.templates.new_layout'));
    });

    it('serves the template preview with manage_templates', function () {
        actingAsBackendUserWith(['manage_templates', 'manage_templates.preview']);
        $template = $this->createTemplate(['content_html' => '<p>allowed</p>']);

        $response = (new Templates)->run('html', [$template->id]);

        expect($response->getContent())->toContain('<p>allowed</p>');
    });
});

describe('Granular permissions', function () {
    it('refuses to duplicate a template from the list without the create permission', function () {
        actingAsBackendUserWith(['manage_templates']);
        $template = $this->createTemplate(['code' => 'acme::pdf.invoice']);

        $response = $this->listAction('onDuplicateRecord', ['id' => $template->id, 'definition' => 'templates']);

        expect($response->status())->toBeGreaterThanOrEqual(400)
            ->and(Template::whereCode('acme::pdf.invoice_copy')->exists())->toBeFalse();
    });

    it('duplicates a template from the list with the create permission', function () {
        actingAsBackendUserWith(['manage_templates', 'manage_templates.create']);
        $template = $this->createTemplate(['code' => 'acme::pdf.invoice']);

        $this->listAction('onDuplicateRecord', ['id' => $template->id, 'definition' => 'templates'])->assertOk();

        expect(Template::whereCode('acme::pdf.invoice_copy')->exists())->toBeTrue();
    });

    it('refuses to duplicate a layout from the list without the layout create permission', function () {
        actingAsBackendUserWith(['manage_templates', 'manage_layouts', 'manage_templates.create']);
        $layout = $this->createLayout(['code' => 'acme::pdf.layouts.default']);

        $response = $this->listAction('onDuplicateRecord', ['id' => $layout->id, 'definition' => 'layouts']);

        expect($response->status())->toBeGreaterThanOrEqual(400)
            ->and(Layout::whereCode('acme::pdf.layouts.default_copy')->exists())->toBeFalse();
    });

    it('refuses to reset a template from the list without the update permission', function () {
        actingAsBackendUserWith(['manage_templates']);
        $template = $this->createTemplate();

        $response = $this->listAction('onResetRecord', ['id' => $template->id, 'definition' => 'templates']);

        expect($response->status())->toBeGreaterThanOrEqual(400);
    });

    it('refuses to delete a template from the list without the delete permission', function () {
        actingAsBackendUserWith(['manage_templates']);
        $template = $this->createTemplate();

        $response = $this->listAction('onDeleteRecord', ['id' => $template->id, 'definition' => 'templates']);

        expect($response->status())->toBeGreaterThanOrEqual(400)
            ->and(Template::find($template->id))->not->toBeNull();
    });

    it('deletes a template from the list with the delete permission', function () {
        actingAsBackendUserWith(['manage_templates', 'manage_templates.delete']);
        $template = $this->createTemplate();

        $this->listAction('onDeleteRecord', ['id' => $template->id, 'definition' => 'templates'])->assertOk();

        expect(Template::find($template->id))->toBeNull();
    });

    it('refuses the template previews without the preview permission', function () {
        actingAsBackendUserWith(['manage_templates']);
        $template = $this->createTemplate();

        expect(fn () => (new Templates)->run('html', [$template->id]))->toThrow(ForbiddenException::class)
            ->and(fn () => (new Templates)->run('previewpdf', [$template->id]))->toThrow(ForbiddenException::class);
    });

    it('refuses the layout previews without the layout preview permission', function () {
        actingAsBackendUserWith(['manage_layouts']);
        $layout = $this->createLayout();

        expect(fn () => (new Layouts)->run('html', [$layout->id]))->toThrow(ForbiddenException::class);
    });

    it('hides the delete button of a view-driven template from a user without the update permission', function () {
        actingAsBackendUserWith(['manage_templates', 'manage_templates.delete']);
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);

        $content = (new Templates)->run('index', ['templates'])->getContent();

        PDFManager::forgetInstance();

        expect($content)->not->toContain('onDeleteRecord')
            ->and($content)->not->toContain('onResetRecord');
    });

    it('hides the new template button without the create permission', function () {
        actingAsBackendUserWith(['manage_templates']);

        $content = (new Templates)->run('index', ['templates'])->getContent();

        expect($content)->not->toContain('renatio/dynamicpdf/templates/create');
    });
});

describe('Guarded controller actions', function () {
    beforeEach(function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);

        $this->records = [
            ':template' => $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'title' => 'Edited', 'is_custom' => true])->id,
            ':layout' => $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.layouts.default', 'name' => 'Edited', 'is_locked' => true])->id,
            ':custom_layout' => $this->createLayout(['code' => 'acme::pdf.layouts.custom'])->id,
        ];

        (new SyncTemplates)->handle();
    });

    afterEach(fn () => PDFManager::forgetInstance());

    it('refuses the action without its permission and leaves the records untouched', function (string $permission, string $path, ?string $handler) {
        actingAsPdfManager([$permission]);
        $before = pdfRecordsSnapshot();

        $response = callGuardedAction($this, $path, $handler);

        expect($response->status())->toBeGreaterThanOrEqual(400)
            ->and(pdfRecordsSnapshot())->toEqual($before);
    })->with('guarded actions');

    it('performs the action with every permission', function (string $permission, string $path, ?string $handler) {
        actingAsPdfManager();
        $before = pdfRecordsSnapshot();

        $response = callGuardedAction($this, $path, $handler);

        $changesRecords = $handler !== null && ! str_contains($handler, '::');

        expect($response->status())->toBeLessThan(400);

        if ($changesRecords) {
            expect(pdfRecordsSnapshot())->not->toEqual($before);
        }
    })->with('guarded actions');

    /**
     * FormController::update() already refuses the page action these handlers run behind, so only a
     * direct call reaches their own guard.
     */
    it('refuses a direct reset without the update permission', function (Closure $controller, string $permission, string $record) {
        actingAsPdfManager([$permission]);
        $before = pdfRecordsSnapshot();

        expect(fn () => $controller()->update_onResetDefault($this->records[$record]))->toThrow(ForbiddenException::class)
            ->and(pdfRecordsSnapshot())->toEqual($before);
    })->with([
        'template' => [fn (): Templates => new Templates, 'manage_templates.update', ':template'],
        'layout' => [fn (): Layouts => new Layouts, 'manage_layouts.update', ':layout'],
    ]);
});

dataset('guarded actions', [
    'duplicate a template' => ['manage_templates.create', 'templates/update/:template', 'onDuplicate'],
    'reset a template' => ['manage_templates.update', 'templates/update/:template', 'onResetDefault'],
    'create a template' => ['manage_templates.create', 'templates/create', 'onSave'],
    'save a template' => ['manage_templates.update', 'templates/update/:template', 'onSave'],
    'delete a template' => ['manage_templates.delete', 'templates/update/:template', 'onDelete'],
    'open the templates list' => ['manage_templates', 'templates', null],
    'duplicate a layout' => ['manage_layouts.create', 'layouts/update/:layout', 'onDuplicate'],
    'reset a layout' => ['manage_layouts.update', 'layouts/update/:layout', 'onResetDefault'],
    'create a layout' => ['manage_layouts.create', 'layouts/create', 'onSave'],
    'save a layout' => ['manage_layouts.update', 'layouts/update/:layout', 'onSave'],
    'delete a layout' => ['manage_layouts.delete', 'layouts/update/:layout', 'onDelete'],
    'open a layout' => ['manage_layouts', 'layouts/update/:layout', null],
    'preview a layout as PDF' => ['manage_layouts.preview', 'layouts/previewpdf/:layout', null],
    'delete a layout from the templates list' => ['manage_layouts', 'templates', 'onDeleteRecord'],
    'refresh the layouts list widget' => ['manage_layouts', 'templates', 'layouts::onRefresh'],
]);

/**
 * @return TestResponse<Response>
 */
function callGuardedAction(TestCase $test, string $path, ?string $handler): TestResponse
{
    $url = Backend::url('renatio/dynamicpdf/' . strtr($path, $test->records));

    if ($handler === null) {
        return $test->get($url);
    }

    return $test->post($url, [
        'id' => $test->records[':custom_layout'],
        'definition' => 'layouts',
        'Template' => ['title' => 'Posted', 'code' => 'acme::pdf.posted', 'content_html' => '<p>posted</p>'],
        'Layout' => ['name' => 'Posted', 'code' => 'acme::pdf.layouts.posted', 'content_html' => '<html><body>posted</body></html>'],
    ], ['X-AJAX-HANDLER' => $handler, 'X-Requested-With' => 'XMLHttpRequest']);
}

/**
 * @return array<int, array<int, object>>
 */
function pdfRecordsSnapshot(): array
{
    return [
        DB::table('renatio_dynamicpdf_pdf_templates')->orderBy('id')->get()->all(),
        DB::table('renatio_dynamicpdf_pdf_layouts')->orderBy('id')->get()->all(),
    ];
}
