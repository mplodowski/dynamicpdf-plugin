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
