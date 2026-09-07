<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
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
            ->and($options->getAllowedRemoteHosts())->toBe(['app.example.com'])
            ->and($options->validateRemoteUri('http://169.254.169.254/latest/meta-data/')[0])->toBeFalse();
    });

    it('ignores the Host header of the preview request', function () {
        config(['app.url' => 'https://app.example.com']);
        Facade::clearResolvedInstance('request');
        app()->instance('request', Request::create('https://169.254.169.254/backend', 'GET'));
        $wrapper = captureWrapper();
        $layout = $this->createLayout(['content_html' => '<html><body>Hello</body></html>']);

        (new Layouts)->previewPdf($layout->id);

        expect($wrapper->getDomPDF()->getOptions()->getAllowedRemoteHosts())->toBe(['app.example.com']);
    });

    it('keeps the configured remote hosts next to the application host', function () {
        config(['app.url' => 'https://app.example.com', 'dompdf.options.allowed_remote_hosts' => ['cdn.example.com']]);
        $wrapper = captureWrapper();
        $layout = $this->createLayout(['content_html' => '<html><body>Hello</body></html>']);

        (new Layouts)->previewPdf($layout->id);

        expect($wrapper->getDomPDF()->getOptions()->getAllowedRemoteHosts())->toBe(['cdn.example.com', 'app.example.com']);
    });

    it('keeps redirects disabled when accepting self-signed certificates', function () {
        $wrapper = captureWrapper();
        $template = $this->createTemplate(['content_html' => '<p>Hello</p>']);

        (new Templates)->previewPdf($template->id);

        expect(stream_context_get_options($wrapper->getDomPDF()->getHttpContext())['http']['follow_location'])->toBeFalse();
    });
});
