<?php

use Endroid\QrCode\QrCode;
use Illuminate\Support\Facades\Artisan;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Renatio\DynamicPDF\Controllers\Templates;
use Twig\Error\RuntimeError;

describe('pdf_qr()', function () {
    it('renders a QR code as a PNG data URI', function () {
        $template = $this->createTemplate(['content_html' => "{{ pdf_qr('https://example.com/invoices/123') }}"]);

        $uri = app('dynamicpdf')->parseTemplate($template);

        expect($uri)->toStartWith('data:image/png;base64,')
            ->and(substr(base64_decode(substr($uri, strlen('data:image/png;base64,'))), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
    });

    it('embeds the QR code in the PDF without remote resources', function () {
        config(['dompdf.options.enable_remote' => false]);
        $template = $this->createTemplate(['content_html' => "<img src=\"{{ pdf_qr('https://example.com/invoices/123') }}\">"]);

        expect(app('dynamicpdf')->loadTemplate($template->code)->output())->toContain('/Subtype /Image');
    });

    it('explains text it cannot encode instead of failing inside the library', function () {
        $template = $this->createTemplate(['content_html' => "{{ pdf_qr('" . str_repeat('x', 5000) . "') }}"]);

        expect(fn () => app('dynamicpdf')->parseTemplate($template))
            ->toThrow(RuntimeError::class, 'pdf_qr() cannot create a QR code for this text');
    });

    it('names the argument that is out of range', function () {
        $template = $this->createTemplate(['content_html' => "{{ pdf_qr('https://example.com', 0) }}"]);

        expect(fn () => app('dynamicpdf')->parseTemplate($template))
            ->toThrow(RuntimeError::class, 'The size argument of pdf_qr() has an invalid value');
    });
})->skip(! class_exists(QrCode::class), 'endroid/qr-code is not installed');

describe('pdf_barcode()', function () {
    it('names the package to install when it is missing', function () {
        actingAsPdfManager();
        $template = $this->createTemplate(['content_html' => "<p>{{ pdf_barcode('5901234123457', 'EAN13') }}</p>"]);

        Artisan::call('dynamicpdf:check');

        expect((new Templates)->html($template->id)->getContent())
            ->toContain('pdf_barcode() needs the picqer/php-barcode-generator package. Install it with composer require picqer/php-barcode-generator')
            ->and(Artisan::output())->toContain('not installed; run composer require picqer/php-barcode-generator');
    })->skip(class_exists(BarcodeGeneratorPNG::class), 'picqer/php-barcode-generator is installed');

    it('renders an EAN-13 barcode as a PNG data URI', function () {
        $template = $this->createTemplate(['content_html' => "{{ pdf_barcode('5901234123457', 'EAN13') }}"]);

        $uri = app('dynamicpdf')->parseTemplate($template);

        expect(substr(base64_decode(substr($uri, strlen('data:image/png;base64,'))), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
    })->skip(! class_exists(BarcodeGeneratorPNG::class), 'picqer/php-barcode-generator is not installed');

    it('explains a wrong EAN-13 check digit', function () {
        $template = $this->createTemplate(['content_html' => "{{ pdf_barcode('5901234123450', 'EAN13') }}"]);

        expect(fn () => app('dynamicpdf')->parseTemplate($template))
            ->toThrow(RuntimeError::class, 'pdf_barcode() cannot encode "5901234123450" as EAN13, the check digit is wrong');
    })->skip(! class_exists(BarcodeGeneratorPNG::class), 'picqer/php-barcode-generator is not installed');
});
