<?php

use Illuminate\Support\Facades\Artisan;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Template;

describe('dynamicpdf:demo', function () {
    afterEach(function () {
        PDFManager::forgetInstance();
        SyncTemplates::forgetFailures();
    });

    it('fails and lists the codes when a demo template could not be synchronised', function () {
        Template::creating(fn () => throw new RuntimeException('cannot write'));

        $exitCode = Artisan::call('dynamicpdf:demo');

        expect($exitCode)->toBe(1)
            ->and(Artisan::output())->toContain('renatio.dynamicpdf::pdf.invoice');
    });

    it('succeeds when everything synchronises', function () {
        expect(Artisan::call('dynamicpdf:demo'))->toBe(0);
    });
});
