<?php

use Backend\Facades\BackendAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Filesystem;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('List actions', function () {
    beforeEach(function () {
        actingAsBackendUserWith(['manage_templates']);
        $this->views = '';
    });

    afterEach(function () {
        if ($this->views !== '') {
            Filesystem::deleteDirectory($this->views);
        }

        BackendAuth::logout();
        PDFManager::forgetInstance();
    });

    it('duplicates a template from the list and redirects to the copy', function () {
        $template = $this->createTemplate(['code' => 'acme::pdf.invoice']);

        $response = $this->listAction('onDuplicateRecord', ['id' => $template->id, 'definition' => 'templates']);
        $copy = Template::whereCode('acme::pdf.invoice_copy')->firstOrFail();

        expect((string) $response->json('__ajax.redirect'))->toContain('templates/update/' . $copy->id);
    });

    it('resets a customised view-driven template from the list', function () {
        $this->views = $this->registerViewTemplates('listactions', ['a' => "title = \"From view\"\n==\n<p>from view</p>"]);
        $template = $this->createTemplate(['code' => 'listactions::pdf.a', 'title' => 'Edited', 'content_html' => '<p>edited</p>', 'is_custom' => true]);

        $this->listAction('onResetRecord', ['id' => $template->id, 'definition' => 'templates'])->assertOk();

        $row = DB::table('renatio_dynamicpdf_pdf_templates')->where('id', $template->id)->first();

        expect($row?->is_custom)->toBeFalsy()
            ->and((string) $row?->content_html)->toContain('from view');
    });

    it('deletes a backend template from the list', function () {
        $template = $this->createTemplate();

        $this->listAction('onDeleteRecord', ['id' => $template->id, 'definition' => 'templates'])->assertOk();

        expect(Template::find($template->id))->toBeNull();
    });

    it('refuses to delete a template that follows a view file', function () {
        $this->views = $this->registerViewTemplates('listactions', ['a' => "title = \"From view\"\n==\n<p>from view</p>"]);
        $template = $this->createTemplate(['code' => 'listactions::pdf.a', 'is_custom' => false]);

        $response = $this->listAction('onDeleteRecord', ['id' => $template->id, 'definition' => 'templates']);

        expect($response->getContent())->toContain(trans('renatio.dynamicpdf::lang.templates.delete_view_refused'))
            ->and(Template::find($template->id))->not->toBeNull();
    });

    it('refuses a layout action without manage_layouts', function () {
        $layout = $this->createLayout(['code' => 'acme::pdf.layouts.default']);

        $response = $this->listAction('onDuplicateRecord', ['id' => $layout->id, 'definition' => 'layouts']);

        expect($response->status())->toBeGreaterThanOrEqual(400)
            ->and(Layout::whereCode('acme::pdf.layouts.default_copy')->exists())->toBeFalse();
    });

    it('duplicates a layout from the list with manage_layouts', function () {
        actingAsBackendUserWith(['manage_templates', 'manage_layouts']);
        $layout = $this->createLayout(['code' => 'acme::pdf.layouts.default']);

        $this->listAction('onDuplicateRecord', ['id' => $layout->id, 'definition' => 'layouts'])->assertOk();

        expect(Layout::whereCode('acme::pdf.layouts.default_copy')->exists())->toBeTrue();
    });
});
