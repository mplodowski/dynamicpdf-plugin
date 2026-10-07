<?php

describe('pdfasset filter', function () {
    beforeEach(function () {
        $this->render = fn (string $path, bool $forBrowser = false): string => app('dynamicpdf')
            ->forBrowser($forBrowser)
            ->parseTemplate($this->createTemplate(['content_html' => "[{{ '{$path}'|pdfasset }}]"]));
    });

    it('resolves a plugin asset to its local file for the PDF', function () {
        expect(($this->render)('plugins/renatio/dynamicpdf/assets/img/october.png'))
            ->toBe('[' . realpath(plugins_path('renatio/dynamicpdf/assets/img/october.png')) . ']');
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
});
