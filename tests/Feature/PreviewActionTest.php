<?php

use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;

describe('Preview PDF action', function () {
    it('is reachable from the lowercase URL October 4.3.5 requires', function () {
        expect((new Templates)->actionExists('previewpdf'))->toBeTrue()
            ->and((new Layouts)->actionExists('previewpdf'))->toBeTrue();
    });
});
