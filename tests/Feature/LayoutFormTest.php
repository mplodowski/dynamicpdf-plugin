<?php

use October\Rain\Support\Facades\Site;
use Renatio\DynamicPDF\Classes\SyncTemplates;

describe('Layout form', function () {
    beforeEach(function () {
        $this->site = $this->enableTranslation('de');

        $this->directory = $this->registerViewLayouts('acme', [
            'layouts/default' => "name = \"Default\"\n==\n<html><body>EN-LAYOUT {{ content_html|raw }}</body></html>",
        ]);
        $this->writeViewFiles('acme', [
            'layouts/de/default' => "name = \"Standard\"\n==\n<html><body>DE-LAYOUT {{ content_html|raw }}</body></html>",
        ], $this->directory);
        $this->registerViewTemplates('acme', [
            'invoice' => "title = \"Invoice\"\nlayout = \"acme::pdf.layouts.default\"\n==\n<p>Body</p>",
        ], $this->directory);

        (new SyncTemplates)->handle();

        $this->layout = $this->findLayout('acme::pdf.layouts.default');

        actingAsPdfManager();
    });

    it('renders a layout edited in the backend over its localized view', function () {
        $this->saveLayoutForm($this->layout->id, [
            'name' => 'Default',
            'content_html' => '<html><body>EDITED-LAYOUT {{ content_html|raw }}</body></html>',
            'content_css' => '',
        ])->assertOk();

        $html = app('dynamicpdf')->loadTemplate('acme::pdf.invoice', locale: 'de')->getDomPDF()->outputHtml();

        expect($this->findLayout('acme::pdf.layouts.default')->is_locked)->toBeFalse()
            ->and($html)->toContain('EDITED-LAYOUT')->not->toContain('DE-LAYOUT');
    });

    it('keeps a view-driven layout following its view when a translation is saved', function () {
        Site::withContext($this->site->id, fn () => $this->saveLayoutForm($this->layout->id, [
            'name' => 'Default',
            'content_html' => '<html><body>GESPEICHERT {{ content_html|raw }}</body></html>',
            'content_css' => '',
        ]))->assertOk();

        $stored = $this->findLayout('acme::pdf.layouts.default');

        expect($stored->is_locked)->toBeTrue()
            ->and($stored->getTranslation('content_html', 'de', false))->toContain('GESPEICHERT');
    });
});
