<?php

describe('PDFWrapper', function () {
    it('forwards option setters to dompdf', function () {
        $wrapper = app('dynamicpdf');

        expect($wrapper->setDpi(300))->toBe($wrapper)
            ->and($wrapper->getDomPDF()->getOptions()->getDpi())->toBe(300);
    });

    it('throws on an unknown method instead of ignoring it', function () {
        expect(fn () => app('dynamicpdf')->__call('setNoSuchOption', [300]))->toThrow(UnexpectedValueException::class);
    });

    it('throws for a protected helper instead of exposing it', function () {
        expect(fn () => app('dynamicpdf')->__call('applyCertificatePolicy', []))->toThrow(UnexpectedValueException::class);
    });

    it('keeps the ssl stream options scalar when the certificate policy is applied twice', function () {
        $wrapper = app('dynamicpdf');
        $wrapper->allowSelfSignedCertificates();
        $wrapper->allowSelfSignedCertificates();

        $ssl = stream_context_get_options($wrapper->getDomPDF()->getHttpContext())['ssl'];

        expect($ssl['verify_peer'])->toBe(false)
            ->and($ssl['verify_peer_name'])->toBe(false)
            ->and($ssl['allow_self_signed'])->toBe(true);
    });

    it('renders a template inside its layout with Twig data', function () {
        $layout = $this->createLayout([
            'content_html' => '<html><body>{{ content_html|raw }}</body></html>',
        ]);
        $template = $this->createTemplate([
            'content_html' => '<p>Hello {{ name }}</p>',
            'layout_id' => $layout->id,
        ]);

        $html = app('dynamicpdf')->parseTemplate($template, ['name' => 'World']);

        expect($html)->toBe('<html><body><p>Hello World</p></body></html>');
    });

    it('applies the paper size and orientation of the template', function (?string $orientation, string $expected, array $size) {
        $this->createTemplate(['code' => 'acme::pdf.paper', 'size' => 'a5', 'orientation' => $orientation]);

        $dompdf = app('dynamicpdf')->loadTemplate('acme::pdf.paper')->getDomPDF();

        expect($dompdf->getPaperOrientation())->toBe($expected)
            ->and($dompdf->getPaperSize())->toBe($size);
    })->with([
        'landscape' => ['landscape', 'landscape', [0.0, 0.0, 595.28, 419.53]],
        'no orientation' => [null, 'portrait', [0.0, 0.0, 419.53, 595.28]],
    ]);

    it('applies the template orientation on the default paper size when no size is set', function () {
        $this->createTemplate(['code' => 'acme::pdf.paper', 'size' => null, 'orientation' => 'landscape']);

        $dompdf = app('dynamicpdf')->loadTemplate('acme::pdf.paper')->getDomPDF();
        [, , $width, $height] = $dompdf->getPaperSize();

        expect($dompdf->getPaperOrientation())->toBe('landscape')
            ->and($width)->toBeGreaterThan($height);
    });

    it('renders an empty string for a template with null content_html', function () {
        $template = $this->createTemplate();
        $template->content_html = null;

        expect(app('dynamicpdf')->parseTemplate($template))->toBe('');
    });

    it('renders an empty string for a layout with null content_html', function () {
        $layout = $this->createLayout();
        $layout->content_html = null;

        expect(app('dynamicpdf')->parseLayout($layout))->toBe('');
    });
});
