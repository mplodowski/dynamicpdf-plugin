<?php

use Barryvdh\DomPDF\PDF;
use Renatio\DynamicPDF\Classes\PDFWrapper;

describe('PDFWrapper Class', function () {
    describe('Class Structure', function () {
        it('extends DomPDF PDF class', function () {
            $reflection = new ReflectionClass(PDFWrapper::class);

            expect($reflection->getParentClass()?->getName())->toBe(PDF::class);
        });

        it('has required methods', function (string $method) {
            expect(method_exists(PDFWrapper::class, $method))->toBeTrue();
        })->with([
            'loadTemplate',
            'loadLayout',
            'parseTemplate',
            'parseLayout',
        ]);
    });

    describe('Method Visibility', function () {
        it('loadTemplate is public', function () {
            $reflection = new ReflectionClass(PDFWrapper::class);
            $method = $reflection->getMethod('loadTemplate');

            expect($method->isPublic())->toBeTrue();
        });

        it('loadLayout is public', function () {
            $reflection = new ReflectionClass(PDFWrapper::class);
            $method = $reflection->getMethod('loadLayout');

            expect($method->isPublic())->toBeTrue();
        });

        it('parseTemplate is public', function () {
            $reflection = new ReflectionClass(PDFWrapper::class);
            $method = $reflection->getMethod('parseTemplate');

            expect($method->isPublic())->toBeTrue();
        });

        it('parseLayout is public', function () {
            $reflection = new ReflectionClass(PDFWrapper::class);
            $method = $reflection->getMethod('parseLayout');

            expect($method->isPublic())->toBeTrue();
        });

        it('layoutData is protected', function () {
            $reflection = new ReflectionClass(PDFWrapper::class);
            $method = $reflection->getMethod('layoutData');

            expect($method->isProtected())->toBeTrue();
        });

        it('parseMarkup is protected', function () {
            $reflection = new ReflectionClass(PDFWrapper::class);
            $method = $reflection->getMethod('parseMarkup');

            expect($method->isProtected())->toBeTrue();
        });

        it('allowSelfSignedCertificates is protected', function () {
            $reflection = new ReflectionClass(PDFWrapper::class);
            $method = $reflection->getMethod('allowSelfSignedCertificates');

            expect($method->isProtected())->toBeTrue();
        });
    });

    describe('Rendering', function () {
        it('parses a template into HTML', function () {
            $layout = $this->createLayout([
                'content_html' => '<html><body>{{ content_html|raw }}</body></html>',
            ]);
            $template = $this->createTemplate([
                'content_html' => '<p>Hello {{ name }}</p>',
                'layout_id' => $layout->id,
            ]);

            $html = app('dynamicpdf')->parseTemplate($template, ['name' => 'World']);

            expect($html)->toContain('Hello World');
        });

        it('parses a template with null content_html', function () {
            $template = $this->createTemplate();
            $template->content_html = null;

            expect(app('dynamicpdf')->parseTemplate($template))->toBe('');
        });

        it('parses a layout with null content_html', function () {
            $layout = $this->createLayout();
            $layout->content_html = null;

            expect(app('dynamicpdf')->parseLayout($layout))->toBe('');
        });
    });
});
