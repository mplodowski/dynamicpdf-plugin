<?php

use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;

describe('HTML preview', function () {
    it('serves the template HTML sandboxed', function () {
        $template = $this->createTemplate(['content_html' => '<p>Hello</p><script>alert(1)</script>']);

        $response = (new Templates)->html($template->id);

        expect($response->headers->get('Content-Security-Policy'))->toBe('sandbox')
            ->and($response->getContent())->toContain('<p>Hello</p>');
    });

    it('serves the layout HTML sandboxed', function () {
        $layout = $this->createLayout(['content_html' => '<html><body>Hello</body></html>']);

        $response = (new Layouts)->html($layout->id);

        expect($response->headers->get('Content-Security-Policy'))->toBe('sandbox');
    });
});
