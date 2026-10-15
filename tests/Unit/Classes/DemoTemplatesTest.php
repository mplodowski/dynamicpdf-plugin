<?php

use Endroid\QrCode\QrCode;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Template;

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

    it('shows a QR code on the invoice only when its package is installed, still on one page', function () {
        $html = PDF::parseTemplate(Template::byCode('renatio.dynamicpdf::pdf.invoice'));

        $pdf = PDF::loadTemplate('renatio.dynamicpdf::pdf.invoice');
        $pdf->render();

        expect(str_contains($html, 'src="data:image/'))->toBe(class_exists(QrCode::class))
            ->and($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1);
    });

    it('renders the demo without CSS or asset warnings with remote assets disabled', function (string $code) {
        $pdf = PDF::loadTemplate($code);

        expect($pdf->getDomPDF()->getOptions()->getIsRemoteEnabled())->toBeFalse();

        $pdf->render();

        $dompdfWarnings = preg_grep('/CSS|Error loading|Permission denied|remote file/', $GLOBALS['_dompdf_warnings']);

        expect($dompdfWarnings)->toBeEmpty();
    })->with(['renatio.dynamicpdf::pdf.invoice', 'renatio.dynamicpdf::pdf.header_and_footer']);
});
