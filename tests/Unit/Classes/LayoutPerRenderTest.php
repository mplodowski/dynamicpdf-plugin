<?php

use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Template;

describe('Layout per render', function () {
    afterEach(fn () => PDFManager::forgetInstance());

    it('renders a template with another layout without touching the stored record', function () {
        $default = $this->createLayout(['code' => 'acme::pdf.layouts.default', 'content_html' => '<html><body class="default">{{ content_html|raw }}</body></html>']);
        $this->createLayout(['code' => 'acme::pdf.layouts.other', 'content_html' => '<html><body class="other">{{ content_html|raw }}</body></html>']);
        $this->createTemplate(['code' => 'acme::pdf.invoice', 'layout_id' => $default->id]);

        $wrapper = app('dynamicpdf')->loadTemplate('acme::pdf.invoice', layout: 'acme::pdf.layouts.other');
        $html = $wrapper->getDomPDF()->outputHtml();
        $wrapper->output();

        $storedLayoutId = (int) Template::whereCode('acme::pdf.invoice')->firstOrFail()->layout_id;

        expect($html)->toContain('class="other"')
            ->and($storedLayoutId)->toBe($default->id);
    });

    it('overrides the layout declared by the view of a non-customised template', function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.layouts.default', 'content_html' => '<html><body class="default">{{ content_html|raw }}</body></html>']);
        $this->createLayout(['code' => 'acme::pdf.layouts.other', 'content_html' => '<html><body class="other">{{ content_html|raw }}</body></html>']);
        $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);

        $wrapper = app('dynamicpdf')->loadTemplate('renatio.dynamicpdf::pdf.invoice', layout: 'acme::pdf.layouts.other');

        expect($wrapper->getDomPDF()->outputHtml())->toContain('class="other"');
    });
});
