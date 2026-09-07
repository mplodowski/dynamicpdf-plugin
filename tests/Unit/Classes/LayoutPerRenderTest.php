<?php

use Renatio\DynamicPDF\Models\Template;

describe('Layout per render', function () {
    it('renders a template with another layout without touching the stored record', function () {
        $default = $this->createLayout(['code' => 'acme::pdf.layouts.default', 'content_html' => '<html><body class="default">{{ content_html|raw }}</body></html>']);
        $this->createLayout(['code' => 'acme::pdf.layouts.other', 'content_html' => '<html><body class="other">{{ content_html|raw }}</body></html>']);
        $template = $this->createTemplate(['code' => 'acme::pdf.invoice', 'layout_id' => $default->id]);

        $wrapper = app('dynamicpdf')->loadTemplate('acme::pdf.invoice', layout: 'acme::pdf.layouts.other');

        $storedLayoutId = (int) Template::whereCode('acme::pdf.invoice')->firstOrFail()->layout_id;

        expect($wrapper->getDomPDF()->outputHtml())->toContain('class="other"')
            ->and($storedLayoutId)->toBe($default->id);
    });

    it('keeps the stored layout when no override is given', function () {
        $default = $this->createLayout(['code' => 'acme::pdf.layouts.default', 'content_html' => '<html><body class="default">{{ content_html|raw }}</body></html>']);
        $this->createTemplate(['code' => 'acme::pdf.invoice', 'layout_id' => $default->id]);

        $wrapper = app('dynamicpdf')->loadTemplate('acme::pdf.invoice');

        expect($wrapper->getDomPDF()->outputHtml())->toContain('class="default"');
    });
});
