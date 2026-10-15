<?php

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Illuminate\Support\Facades\Artisan;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Renatio\DynamicPDF\Classes\Codes;
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

    it('takes options in place of the size or after the margin, keeping the defaults it does not set', function () {
        $render = fn (string $call): string => app('dynamicpdf')->parseTemplate($this->createTemplate(['content_html' => "{{ {$call} }}"]));

        $default = $render("pdf_qr('x')");
        $colored = $render("pdf_qr('x', {color: '#1f2937'})");

        expect($colored)->not->toBe($default)
            ->and($render("pdf_qr('x', 150, 0, {color: '#1F2937'})"))->toBe($colored)
            ->and($render("pdf_qr('x', {size: 150, margin: 0, color: '#000', background: '#ffffff', error_correction: 'LOW'})"))->toBe($default);
    });

    it('explains an option it cannot use', function (string $options, string $message) {
        $template = $this->createTemplate(['content_html' => "{{ pdf_qr('x', {$options}) }}"]);

        expect(fn () => app('dynamicpdf')->parseTemplate($template))->toThrow(RuntimeError::class, $message);
    })->with([
        'unknown option' => ["{logo: 'a.png'}", 'logo is not an option of pdf_qr(). Use one of size, margin, color, background, error_correction'],
        'color name' => ["{color: 'red'}", 'The color argument of pdf_qr() has an invalid value'],
        'error correction level' => ["{error_correction: 'max'}", 'The error_correction argument of pdf_qr() has an invalid value'],
    ]);

    it('is unavailable and names the major version to install when an older endroid/qr-code is installed', function () {
        $codes = new class extends Codes
        {
            public const PACKAGES = ['pdf_qr()' => ['class' => 'Endroid\\QrCode\\Missing'] + parent::PACKAGES['pdf_qr()']] + parent::PACKAGES;
        };

        expect(Codes::PACKAGES['pdf_qr()']['class'])->toBe(ErrorCorrectionLevel::class)
            ->and($codes->qrAvailable())->toBeFalse()
            ->and(fn () => $codes->qr('x'))->toThrow(RuntimeError::class, 'pdf_qr() needs version 5 or newer of the endroid/qr-code package. Upgrade it with composer require endroid/qr-code:^5.');
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
