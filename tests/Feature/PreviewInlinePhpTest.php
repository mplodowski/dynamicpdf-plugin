<?php

use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;

describe('Backend preview', function () {
    beforeEach(function () {
        $this->wrapper = app('dynamicpdf');
        app()->instance('dynamicpdf', $this->wrapper);
    });

    afterEach(function () {
        app()->forgetInstance('dynamicpdf');
    });

    it('does not enable inline PHP when previewing a template', function () {
        $template = $this->createTemplate(['content_html' => '<p>Hello</p>']);

        (new Templates)->previewPdf($template->id);

        expect($this->wrapper->getDomPDF()->getOptions()->getIsPhpEnabled())->toBeFalse();
    });

    it('does not enable inline PHP when previewing a layout', function () {
        $layout = $this->createLayout(['content_html' => '<html><body>Hello</body></html>']);

        (new Layouts)->previewPdf($layout->id);

        expect($this->wrapper->getDomPDF()->getOptions()->getIsPhpEnabled())->toBeFalse();
    });
});
