<?php

use Backend\Facades\Backend;

describe('Preview PDF action', function () {
    it('is reachable from the lowercase URL October 4.3.5 requires', function (string $definition) {
        actingAsPdfManager();
        $record = $definition === 'templates' ? $this->createTemplate() : $this->createLayout();

        $response = $this->get(Backend::url("renatio/dynamicpdf/{$definition}/previewpdf/{$record->id}"));

        expect($response->status())->toBe(200)
            ->and($response->headers->get('Content-Type'))->toBe('application/pdf');
    })->with(['templates', 'layouts']);
});

describe('Preview HTML action', function () {
    it('frames the preview in the paper size and orientation of the template', function () {
        actingAsPdfManager();
        $template = $this->createTemplate(['size' => 'a4', 'orientation' => 'landscape']);

        $html = $this->get(Backend::url("renatio/dynamicpdf/templates/preview/{$template->id}"))->assertOk()->getContent();

        expect($html)->toContain('width: 1123px; max-width: 100%; aspect-ratio: 1123 / 794;');
    });
});
