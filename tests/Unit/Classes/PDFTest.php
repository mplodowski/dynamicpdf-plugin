<?php

use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Renatio\DynamicPDF\Classes\PDF;

describe('PDF Class', function () {
    describe('Class Structure', function () {
        it('extends DomPDF Facade', function () {
            $reflection = new ReflectionClass(PDF::class);

            expect($reflection->getParentClass()?->getName())->toBe(PdfFacade::class);
        });

        it('has getFacadeAccessor method', function () {
            $reflection = new ReflectionClass(PDF::class);
            $method = $reflection->getMethod('getFacadeAccessor');

            expect($method->isProtected())->toBeTrue();
            expect($method->isStatic())->toBeTrue();
        });
    });

    describe('Facade Accessor', function () {
        it('returns dynamicpdf as facade accessor', function () {
            $reflection = new ReflectionClass(PDF::class);
            $method = $reflection->getMethod('getFacadeAccessor');
            $method->setAccessible(true);

            expect($method->invoke(null))->toBe('dynamicpdf');
        });
    });
});
