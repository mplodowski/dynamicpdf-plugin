<?php

use Renatio\DynamicPDF\Classes\PDFParser;

describe('PDFParser Class', function () {
    describe('Class Structure', function () {
        it('parse is static', function () {
            $reflection = new ReflectionClass(PDFParser::class);
            $method = $reflection->getMethod('parse');

            expect($method->isStatic())->toBeTrue();
        });

        it('sections is static', function () {
            $reflection = new ReflectionClass(PDFParser::class);
            $method = $reflection->getMethod('sections');

            expect($method->isStatic())->toBeTrue();
        });
    });

    describe('Parse Method', function () {
        it('returns array with settings, css, and html keys', function () {
            $result = PDFParser::parse('');

            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['settings', 'css', 'html']);
        });

        it('parses content with only HTML', function () {
            $content = '<p>Hello World</p>';
            $result = PDFParser::parse($content);

            expect($result['html'])->toBe('<p>Hello World</p>');
            expect($result['settings'])->toBeEmpty();
            expect($result['css'])->toBeNull();
        });

        it('parses content with settings and HTML', function () {
            $content = "name = \"Test\"\n==\n<p>Hello</p>";
            $result = PDFParser::parse($content);

            expect($result['settings'])->toHaveKey('name');
            expect($result['settings']['name'])->toBe('Test');
            expect($result['html'])->toBe('<p>Hello</p>');
        });

        it('parses content with settings, CSS, and HTML', function () {
            $content = "name = \"Test\"\n==\nbody { color: red; }\n==\n<p>Hello</p>";
            $result = PDFParser::parse($content);

            expect($result['settings']['name'])->toBe('Test');
            expect($result['css'])->toBe('body { color: red; }');
            expect($result['html'])->toBe('<p>Hello</p>');
        });

        it('trims whitespace from sections', function () {
            $content = "  name = \"Test\"  \n==\n  <p>Hello</p>  ";
            $result = PDFParser::parse($content);

            expect($result['html'])->toBe('<p>Hello</p>');
        });
    });
});
