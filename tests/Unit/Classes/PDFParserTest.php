<?php

use Renatio\DynamicPDF\Classes\PDFParser;

describe('PDFParser', function () {
    it('parses content with only HTML', function () {
        $result = PDFParser::parse('<p>Hello World</p>');

        expect($result)->toMatchArray([
            'settings' => [],
            'css' => null,
            'html' => '<p>Hello World</p>',
        ]);
    });

    it('parses content with settings and HTML', function () {
        $result = PDFParser::parse("name = \"Test\"\n==\n<p>Hello</p>");

        expect($result['settings']['name'])->toBe('Test')
            ->and($result['css'])->toBeNull()
            ->and($result['html'])->toBe('<p>Hello</p>');
    });

    it('parses content with settings, CSS and HTML', function () {
        $result = PDFParser::parse("name = \"Test\"\n==\nbody { color: red; }\n==\n<p>Hello</p>");

        expect($result['settings']['name'])->toBe('Test')
            ->and($result['css'])->toBe('body { color: red; }')
            ->and($result['html'])->toBe('<p>Hello</p>');
    });

    it('trims whitespace from sections', function () {
        $result = PDFParser::parse("  name = \"Test\"  \n==\n  <p>Hello</p>  ");

        expect($result['html'])->toBe('<p>Hello</p>');
    });
});
