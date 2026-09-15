<?php

use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;

describe('Preview PDF action', function () {
    it('is reachable from the lowercase URL October 4.3.5 requires', function () {
        expect(get_class_methods(Templates::class))->toContain('previewpdf')
            ->and(get_class_methods(Layouts::class))->toContain('previewpdf');
    });
});
