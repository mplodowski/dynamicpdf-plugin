<?php

use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;

describe('Demo templates', function () {
    beforeEach(function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice', 'renatio.dynamicpdf::pdf.header_and_footer']);
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default', 'renatio.dynamicpdf::pdf.layouts.header_and_footer']);
        $GLOBALS['_dompdf_warnings'] = [];
    });

    it('renders the header and footer demo on the two pages its single page break asks for', function () {
        $pdf = PDF::loadTemplate('renatio.dynamicpdf::pdf.header_and_footer');
        $pdf->render();

        expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(2);
    });

    it('renders the demo without a single dompdf warning with remote assets disabled', function (string $code) {
        $pdf = PDF::loadTemplate($code);

        expect($pdf->getDomPDF()->getOptions()->getIsRemoteEnabled())->toBeFalse();

        $pdf->render();

        expect($GLOBALS['_dompdf_warnings'])->toBeEmpty();
    })->with(['renatio.dynamicpdf::pdf.invoice', 'renatio.dynamicpdf::pdf.header_and_footer']);
});
