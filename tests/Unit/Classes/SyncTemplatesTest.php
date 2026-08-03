<?php

use Renatio\DynamicPDF\Classes\SyncTemplates;

describe('SyncTemplates Class', function () {
    describe('Class Structure', function () {
        it('handle is public', function () {
            $reflection = new ReflectionClass(SyncTemplates::class);
            $method = $reflection->getMethod('handle');

            expect($method->isPublic())->toBeTrue();
        });
    });

    describe('Protected Methods', function () {
        it('has checkFontsDir method', function () {
            $reflection = new ReflectionClass(SyncTemplates::class);

            expect($reflection->hasMethod('checkFontsDir'))->toBeTrue();
        });

        it('has createLayouts method', function () {
            $reflection = new ReflectionClass(SyncTemplates::class);

            expect($reflection->hasMethod('createLayouts'))->toBeTrue();
        });

        it('has clearNonCustomizedTemplates method', function () {
            $reflection = new ReflectionClass(SyncTemplates::class);

            expect($reflection->hasMethod('clearNonCustomizedTemplates'))->toBeTrue();
        });

        it('has createTemplates method', function () {
            $reflection = new ReflectionClass(SyncTemplates::class);

            expect($reflection->hasMethod('createTemplates'))->toBeTrue();
        });

        it('has scanTranslatedMessages method', function () {
            $reflection = new ReflectionClass(SyncTemplates::class);

            expect($reflection->hasMethod('scanTranslatedMessages'))->toBeTrue();
        });
    });

    describe('Handle Method', function () {
        it('executes without throwing exceptions', function () {
            $syncTemplates = new SyncTemplates;

            expect(function () use ($syncTemplates) {
                $syncTemplates->handle();
            })->not->toThrow(Exception::class);
        });
    });
});
