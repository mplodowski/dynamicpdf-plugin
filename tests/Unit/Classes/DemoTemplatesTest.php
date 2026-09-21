<?php

use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Template;

describe('Demo templates', function () {
    afterEach(fn () => PDFManager::forgetInstance());

    it('renders the header and footer demo on the two pages its single page break asks for', function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.header_and_footer']);
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.header_and_footer']);

        $wrapper = app('dynamicpdf');
        $html = $wrapper->parseTemplate(Template::byCode('renatio.dynamicpdf::pdf.header_and_footer'));

        /** The demo references its stylesheet, fonts and background by application URL; read them from disk so the page count is measured styled and offline. */
        $wrapper->loadHTML(str_replace(url('/') . '/', base_path() . '/', $html));
        $wrapper->render();

        expect($wrapper->getDomPDF()->getCanvas()->get_page_count())->toBe(2);
    });
});
