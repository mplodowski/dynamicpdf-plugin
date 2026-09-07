<?php

use Illuminate\Support\Facades\Artisan;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Models\Parameter;

describe('dynamicpdf:demo', function () {
    afterEach(function () {
        Parameter::set('renatio::dynamicpdf.demo', 0);
        PDFManager::forgetInstance();
    });

    it('creates the demo templates and layouts and removes them again', function () {
        Artisan::call('dynamicpdf:demo');

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue()
            ->and(Layout::whereCode('renatio.dynamicpdf::pdf.layouts.default')->exists())->toBeTrue();

        Artisan::call('dynamicpdf:demo', ['--disable' => true]);

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeFalse()
            ->and(Layout::whereCode('renatio.dynamicpdf::pdf.layouts.default')->exists())->toBeFalse();
    });
});
