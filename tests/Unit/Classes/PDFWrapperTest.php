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
});
