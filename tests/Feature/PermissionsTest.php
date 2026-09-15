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

    it('serves the template preview with manage_templates', function () {
        actingAsBackendUserWith(['manage_templates']);
        $template = $this->createTemplate(['content_html' => '<p>allowed</p>']);

        $response = (new Templates)->run('html', [$template->id]);

        expect($response->getContent())->toContain('<p>allowed</p>');
    });
});
