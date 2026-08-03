<?php

use October\Rain\Support\Traits\Singleton;
use Renatio\DynamicPDF\Classes\PDFManager;

describe('PDFManager Class', function () {
    describe('Class Structure', function () {
        it('uses Singleton trait', function () {
            $traits = class_uses(PDFManager::class);

            expect($traits)->toContain(Singleton::class);
        });

        it('has required methods', function (string $method) {
            expect(method_exists(PDFManager::class, $method))->toBeTrue();
        })->with([
            'loadRegisteredTemplates',
            'listRegisteredLayouts',
            'listRegisteredTemplates',
            'registerLayouts',
            'registerTemplates',
        ]);
    });

    describe('Singleton Pattern', function () {
        it('returns same instance', function () {
            $instance1 = PDFManager::instance();
            $instance2 = PDFManager::instance();

            expect($instance1)->toBe($instance2);
        });
    });

    describe('Layout Registration', function () {
        it('registers layouts', function () {
            $manager = PDFManager::instance();

            $manager->registerLayouts(['test.layout.one', 'test.layout.two']);

            $layouts = $manager->listRegisteredLayouts();

            expect($layouts)->toHaveKey('test.layout.one');
            expect($layouts)->toHaveKey('test.layout.two');
        });

        it('listRegisteredLayouts returns array or null', function () {
            $manager = PDFManager::instance();
            $layouts = $manager->listRegisteredLayouts();

            expect($layouts)->toBeArray();
        });
    });

    describe('Template Registration', function () {
        it('registers templates', function () {
            $manager = PDFManager::instance();

            $manager->registerTemplates(['test.template.one', 'test.template.two']);

            $templates = $manager->listRegisteredTemplates();

            expect($templates)->toHaveKey('test.template.one');
            expect($templates)->toHaveKey('test.template.two');
        });

        it('listRegisteredTemplates returns array or null', function () {
            $manager = PDFManager::instance();
            $templates = $manager->listRegisteredTemplates();

            expect($templates)->toBeArray();
        });
    });
});
