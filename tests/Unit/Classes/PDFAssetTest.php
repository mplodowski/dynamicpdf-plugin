<?php

describe('pdfasset filter', function () {
    beforeEach(function () {
        $this->render = function (string $path, bool $forBrowser = false): string {
            $template = $this->createTemplate(['content_html' => "<p>[{{ '{$path}'|pdfasset }}]</p>"]);
            $html = app('dynamicpdf')->forBrowser($forBrowser)->loadTemplate($template->code)->getDomPDF()->outputHtml();

            return preg_match('~<p>(\[.*?\])</p>~s', $html, $match) ? html_entity_decode($match[1]) : '';
        };
    });

    it('resolves a plugin asset to its local file for the PDF', function () {
        expect(($this->render)('plugins/renatio/dynamicpdf/assets/img/october.png'))
            ->toBe('[' . plugins_path('renatio/dynamicpdf/assets/img/october.png') . ']');
    });

    it('keeps the application URL for the browser preview', function () {
        expect(($this->render)('plugins/renatio/dynamicpdf/assets/img/october.png', true))
            ->toBe('[' . url('plugins/renatio/dynamicpdf/assets/img/october.png') . ']');
    });

    it('resolves nothing outside the plugins directory', function (string $path) {
        expect(($this->render)($path))->toBe('[]');
    })->with([
        'escape with ..' => 'plugins/renatio/dynamicpdf/../../../config/app.php',
        'file outside plugins' => 'config/app.php',
        'absolute path' => '/etc/hosts',
        'stream wrapper' => 'file:///etc/hosts',
        'missing file' => 'plugins/renatio/dynamicpdf/assets/img/missing.png',
    ]);

    it('escapes what it returns', function () {
        $template = $this->createTemplate(['content_html' => '<p>{{ \'"><b>x</b>\'|pdfasset }}</p>']);

        expect(app('dynamicpdf')->parseTemplate($template))->not->toContain('<b>x</b>');
    });
});
