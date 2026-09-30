<?php

use Renatio\DynamicPDF\Classes\PDFParser;

describe('PDFParser', function () {
    it('parses content with only HTML', function () {
        $result = (new PDFParser)->parseContent('<p>Hello World</p>');

        expect($result)->toMatchArray([
            'settings' => [],
            'css' => null,
            'html' => '<p>Hello World</p>',
        ]);
    });

    it('parses content with settings and HTML', function () {
        $result = (new PDFParser)->parseContent("name = \"Test\"\n==\n<p>Hello</p>");

        expect($result['settings']['name'])->toBe('Test')
            ->and($result['css'])->toBeNull()
            ->and($result['html'])->toBe('<p>Hello</p>');
    });

    it('parses content with settings, CSS and HTML', function () {
        $result = (new PDFParser)->parseContent("name = \"Test\"\n==\nbody { color: red; }\n==\n<p>Hello</p>");

        expect($result['settings']['name'])->toBe('Test')
            ->and($result['css'])->toBe('body { color: red; }')
            ->and($result['html'])->toBe('<p>Hello</p>');
    });

    it('trims whitespace from sections', function () {
        $result = (new PDFParser)->parseContent("  name = \"Test\"  \n==\n  <p>Hello</p>  ");

        expect($result['html'])->toBe('<p>Hello</p>');
    });

    it('keeps the static calls released in v8.0.3', function () {
        $content = "name = \"Test\"\n==\nbody { color: red; }\n==\n<p>Hello</p>";

        expect(PDFParser::parse($content))->toBe((new PDFParser)->parseContent($content))
            ->and(PDFParser::sections('renatio.dynamicpdf::pdf.layouts.default'))
            ->toBe((new PDFParser)->parseView('renatio.dynamicpdf::pdf.layouts.default'));
    });
});
