<?php

use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;

function captureWrapper(): \Renatio\DynamicPDF\Classes\PDFWrapper
{
    $wrapper = app('dynamicpdf');
    app()->instance('dynamicpdf', $wrapper);

    return $wrapper;
}

describe('Backend preview', function () {
    afterEach(function () {
        app()->forgetInstance('dynamicpdf');
    });

    it('does not enable inline PHP when previewing a template', function () {
        $wrapper = captureWrapper();
        $template = $this->createTemplate(['content_html' => '<p>Hello</p>']);

        (new Templates)->previewPdf($template->id);

        expect($wrapper->getDomPDF()->getOptions()->getIsPhpEnabled())->toBeFalse();
    });

    it('does not enable inline PHP when previewing a layout', function () {
        $wrapper = captureWrapper();
        $layout = $this->createLayout(['content_html' => '<html><body>Hello</body></html>']);

        (new Layouts)->previewPdf($layout->id);

        expect($wrapper->getDomPDF()->getOptions()->getIsPhpEnabled())->toBeFalse();
    });

    it('limits remote resources to the application host when the config allows any host', function () {
        config(['app.url' => 'https://app.example.com']);
        $wrapper = captureWrapper();
        $template = $this->createTemplate(['content_html' => '<p>Hello</p>']);

        (new Templates)->previewPdf($template->id);

        $options = $wrapper->getDomPDF()->getOptions();

        expect($options->getIsRemoteEnabled())->toBeTrue()
            ->and($options->getAllowedRemoteHosts())->toContain('app.example.com')
            ->and($options->validateRemoteUri('http://169.254.169.254/latest/meta-data/')[0])->toBeFalse();
    });

    it('keeps the configured remote host list for a layout preview', function () {
        config(['dompdf.options.allowed_remote_hosts' => ['cdn.example.com']]);
        $wrapper = captureWrapper();
        $layout = $this->createLayout(['content_html' => '<html><body>Hello</body></html>']);

        (new Layouts)->previewPdf($layout->id);

        expect($wrapper->getDomPDF()->getOptions()->getAllowedRemoteHosts())->toBe(['cdn.example.com']);
    });
});
