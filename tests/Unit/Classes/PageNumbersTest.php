<?php

describe('Page numbers', function () {
    it('stamps page numbers on every page without inline PHP', function () {
        $wrapper = app('dynamicpdf');
        $wrapper->loadHTML('<p>one</p><p style="page-break-before: always;">two</p>');
        $wrapper->pageNumbers('Page {PAGE_NUM} of {PAGE_COUNT}');

        $pdf = $wrapper->output(['compress' => 0]);

        expect($wrapper->getDomPDF()->getOptions()->getIsPhpEnabled())->toBeFalse()
            ->and($pdf)->toContain('Page 1 of 2')
            ->and($pdf)->toContain('Page 2 of 2');
    });

    it('renders without page numbers by default', function () {
        $wrapper = app('dynamicpdf');
        $wrapper->loadHTML('<p>one</p>');

        expect($wrapper->output(['compress' => 0]))->not->toContain('Page 1 of 1');
    });
});
