<?php

use October\Rain\Exception\ForbiddenException;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

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
