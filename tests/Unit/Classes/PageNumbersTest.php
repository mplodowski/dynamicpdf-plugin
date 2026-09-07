<?php

describe('Page numbers', function () {
    $twoPages = function (): \Renatio\DynamicPDF\Classes\PDFWrapper {
        $wrapper = app('dynamicpdf');
        $wrapper->setOption(['defaultFont' => 'serif', 'pdfBackend' => 'CPDF']);
        $wrapper->loadHTML('<p>one</p><p style="page-break-before: always;">two</p>');

        return $wrapper;
    };

    it('stamps page numbers on every page without inline PHP', function () use ($twoPages) {
        $wrapper = $twoPages();
        $wrapper->pageNumbers('Page {PAGE_NUM} of {PAGE_COUNT}');

        $pdf = $wrapper->output(['compress' => 0]);

        expect($wrapper->getDomPDF()->getOptions()->getIsPhpEnabled())->toBeFalse()
            ->and($pdf)->toContain('Page 1 of 2')
            ->and($pdf)->toContain('Page 2 of 2');
    });

    it('stamps once even when the document is rendered again', function () use ($twoPages) {
        $wrapper = $twoPages();
        $wrapper->pageNumbers('Page {PAGE_NUM} of {PAGE_COUNT}');
        $wrapper->render();
        $wrapper->render();

        expect(substr_count($wrapper->output(['compress' => 0]), 'Page 1 of 2'))->toBe(1);
    });

    it('rejects a font the document does not know', function () use ($twoPages) {
        $wrapper = $twoPages();
        $wrapper->pageNumbers('Page {PAGE_NUM}', font: 'No Such Family');

        expect(fn () => $wrapper->output())->toThrow(InvalidArgumentException::class);
    });

    it('rejects a colour outside the 0..1 range', function () {
        expect(fn () => app('dynamicpdf')->pageNumbers('Page {PAGE_NUM}', color: [255, 0, 0]))
            ->toThrow(InvalidArgumentException::class);
    });

    it('renders without page numbers by default', function () use ($twoPages) {
        expect($twoPages()->output(['compress' => 0]))->not->toContain('Page 1 of 2');
    });
});
