<?php

use Backend\Facades\BackendAuth;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;

describe('Permissions', function () {
    afterEach(fn () => BackendAuth::logout());

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
        actingAsBackendUserWith(['manage_templates']);
        $this->createLayout();

        $content = (new Templates)->run('index', ['layouts'])->getContent();

        expect($content)->toContain('renatio/dynamicpdf/templates/create')
            ->and($content)->not->toContain('renatio/dynamicpdf/templates/index/layouts')
            ->and($content)->not->toContain(trans('renatio.dynamicpdf::lang.templates.new_layout'));
    });

    it('shows the layouts list with manage_layouts', function () {
        actingAsBackendUserWith(['manage_templates', 'manage_layouts']);
        $this->createLayout();

        $content = (new Templates)->run('index', ['layouts'])->getContent();

        expect($content)->toContain('renatio/dynamicpdf/templates/index/layouts')
            ->and($content)->toContain(trans('renatio.dynamicpdf::lang.templates.new_layout'));
    });

    it('serves the template preview with manage_templates', function () {
        actingAsBackendUserWith(['manage_templates']);
        $template = $this->createTemplate(['content_html' => '<p>allowed</p>']);

        $response = (new Templates)->run('html', [$template->id]);

        expect($response->getContent())->toContain('<p>allowed</p>');
    });
});
