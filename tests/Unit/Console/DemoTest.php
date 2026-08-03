<?php

use Illuminate\Console\Command;
use Renatio\DynamicPDF\Console\Demo;

describe('Demo Command', function () {
    describe('Class Structure', function () {
        it('extends Command', function () {
            $reflection = new ReflectionClass(Demo::class);

            expect($reflection->getParentClass()?->getName())->toBe(Command::class);
        });

        it('has handle method', function () {
            expect(method_exists(Demo::class, 'handle'))->toBeTrue();
        });
    });

    describe('Command Configuration', function () {
        it('has signature property', function () {
            $reflection = new ReflectionClass(Demo::class);

            expect($reflection->hasProperty('signature'))->toBeTrue();
        });

        it('has description property', function () {
            $reflection = new ReflectionClass(Demo::class);

            expect($reflection->hasProperty('description'))->toBeTrue();
        });

        it('signature contains dynamicpdf:demo', function () {
            $reflection = new ReflectionClass(Demo::class);
            $property = $reflection->getProperty('signature');
            $property->setAccessible(true);

            $demo = new Demo;
            $signature = $property->getValue($demo);

            expect($signature)->toContain('dynamicpdf:demo');
        });

        it('signature has disable option', function () {
            $reflection = new ReflectionClass(Demo::class);
            $property = $reflection->getProperty('signature');
            $property->setAccessible(true);

            $demo = new Demo;
            $signature = $property->getValue($demo);

            expect($signature)->toContain('--disable');
        });
    });

    describe('Protected Methods', function () {
        it('has enableDemo method', function () {
            $reflection = new ReflectionClass(Demo::class);

            expect($reflection->hasMethod('enableDemo'))->toBeTrue();
        });

        it('has disableDemo method', function () {
            $reflection = new ReflectionClass(Demo::class);

            expect($reflection->hasMethod('disableDemo'))->toBeTrue();
        });

        it('enableDemo is protected', function () {
            $reflection = new ReflectionClass(Demo::class);
            $method = $reflection->getMethod('enableDemo');

            expect($method->isProtected())->toBeTrue();
        });

        it('disableDemo is protected', function () {
            $reflection = new ReflectionClass(Demo::class);
            $method = $reflection->getMethod('disableDemo');

            expect($method->isProtected())->toBeTrue();
        });
    });
});
