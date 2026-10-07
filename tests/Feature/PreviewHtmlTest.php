<?php

use Illuminate\Support\Facades\File;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;

describe('HTML preview', function () {
    beforeEach(fn () => actingAsPdfManager());

    it('serves the template HTML sandboxed', function () {
        $template = $this->createTemplate(['content_html' => '<p>Hello</p><script>alert(1)</script>']);

        $response = (new Templates)->html($template->id);

        expect($response->headers->get('Content-Security-Policy'))->toBe("sandbox; script-src 'none'; object-src 'none'")
            ->and($response->getContent())->toContain('<p>Hello</p>');
    });

    it('keeps the preview iframes fully sandboxed', function () {
        expect(file_get_contents(plugins_path('renatio/dynamicpdf/controllers/templates/preview.php')))->toContain('<iframe sandbox src=')
            ->and(file_get_contents(plugins_path('renatio/dynamicpdf/controllers/layouts/preview.php')))->toContain('<iframe sandbox src=');
    });

    it('serves the layout HTML sandboxed', function () {
        $layout = $this->createLayout(['content_html' => '<html><body>Hello</body></html>']);

        $response = (new Layouts)->html($layout->id);

        expect($response->headers->get('Content-Security-Policy'))->toBe("sandbox; script-src 'none'; object-src 'none'");
    });

    it('links plugin assets by URL so the browser can load them', function () {
        $layout = $this->createLayout(['content_html' => "<html><body><img src=\"{{ 'plugins/renatio/dynamicpdf/assets/img/october.png'|pdfasset }}\">{{ content_html|raw }}</body></html>"]);
        $template = $this->createTemplate(['layout_id' => $layout->id]);

        expect((new Templates)->html($template->id)->getContent())->toContain(url('plugins/renatio/dynamicpdf/assets/img/october.png'))
            ->and((new Layouts)->html($layout->id)->getContent())->toContain(url('plugins/renatio/dynamicpdf/assets/img/october.png'));
    });
});

describe('HTML preview fonts', function () {
    beforeEach(function () {
        actingAsPdfManager();

        $this->preview = function (string $src) {
            $layout = $this->createLayout(['content_html' => "<html><head><style>@font-face { font-family: 'Custom'; src: {$src}; } body { background: {$src}; }</style></head><body>{{ content_html|raw }}</body></html>"]);
            $template = $this->createTemplate(['layout_id' => $layout->id]);

            return (new Templates)->html($template->id)->getContent();
        };
    });

    it('inlines application fonts declared in @font-face so the sandboxed preview can load them', function () {
        $font = base64_encode(File::get(plugins_path('renatio/dynamicpdf/assets/fonts/OpenSans-Regular.ttf')));
        $layout = $this->createLayout(['content_html' => <<<'HTML'
            <html><head><style>
            @font-face { font-family: 'Open Sans'; src: url('{{ 'plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Regular.ttf'|pdfasset }}'); }
            @font-face { font-family: 'Open Sans'; font-weight: bold; src: local('Open Sans Bold'), url({{ 'plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Bold.ttf'|app }}) format('truetype'), url("/plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Italic.ttf") format("truetype"); }
            </style></head><body>{{ content_html|raw }}</body></html>
            HTML]);
        $template = $this->createTemplate(['layout_id' => $layout->id]);

        $html = (new Templates)->html($template->id)->getContent();

        expect($html)->toContain("src: url('data:font/ttf;base64,{$font}');")
            ->and($html)->toContain("local('Open Sans Bold'), url(data:font/ttf;base64,")
            ->and($html)->toContain('url("data:font/ttf;base64,')
            ->and($html)->not->toContain('OpenSans-');
    });

    it('accepts the host of the current request', function () {
        $this->app['request']->headers->set('HOST', 'preview.example.test');

        expect(($this->preview)("url('http://preview.example.test/plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Regular.ttf')"))
            ->toContain("src: url('data:font/ttf;base64,");
    });

    it('leaves fonts it may not read untouched', function (string $url) {
        expect(($this->preview)("url('{$url}')"))->toContain("src: url('{$url}')");
    })->with([
        'another host' => 'https://fonts.example.com/plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Regular.ttf',
        'escape with ..' => '/plugins/renatio/dynamicpdf/../../../vendor/endroid/qr-code/assets/open_sans.ttf',
        'font outside public directories' => '/vendor/endroid/qr-code/assets/open_sans.ttf',
        'not a font' => '/plugins/renatio/dynamicpdf/assets/img/october.png',
        'config file' => '/config/app.php',
        'missing file' => '/plugins/renatio/dynamicpdf/assets/fonts/Missing.ttf',
    ]);

    it('leaves url() outside @font-face untouched', function () {
        expect(($this->preview)("url('/plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Regular.ttf')"))
            ->toContain("background: url('/plugins/renatio/dynamicpdf/assets/fonts/OpenSans-Regular.ttf')");
    });
});
