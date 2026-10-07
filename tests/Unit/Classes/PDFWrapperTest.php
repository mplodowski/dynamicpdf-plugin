<?php

use Dompdf\Exception as DompdfException;
use Illuminate\Support\Facades\File;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Tests\TestCase;

describe('PDFWrapper', function () {
    it('forwards option setters to dompdf', function () {
        $wrapper = app('dynamicpdf');

        expect($wrapper->setDpi(300))->toBe($wrapper)
            ->and($wrapper->getDomPDF()->getOptions()->getDpi())->toBe(300);
    });

    it('keeps the wrapper through the inherited and forwarded setters of a facade chain', function () {
        $wrapper = PDF::loadHTML('<p>Chained</p>')
            ->setPaper('a5', 'landscape')
            ->setOption('dpi', 120)
            ->addInfo(['Title' => 'Chained'])
            ->setDefaultFont('serif')
            ->pageNumbers();

        expect($wrapper->getDomPDF()->getOptions()->getDpi())->toBe(120)
            ->and($wrapper->output())->toStartWith('%PDF');
    });

    describe('loadFile()', function () {
        beforeEach(function () {
            $this->wrapper = app('dynamicpdf');
            $this->presetBase = $this->wrapper->getDomPDF()->getBasePath();
            $this->directory = storage_path('temp/dynamicpdf-' . uniqid());
            File::ensureDirectoryExists($this->directory);
            file_put_contents($this->directory . '/document.html', '<p>From file</p>');
        });

        afterEach(fn () => File::deleteDirectory($this->directory));

        it('refuses a file outside the chroot and keeps the preset base', function () {
            $tempFile = tempnam(sys_get_temp_dir(), 'dpdf');
            $file = $tempFile . '.html';
            rename($tempFile, $file);

            try {
                expect(fn () => $this->wrapper->loadFile($file))->toThrow(DompdfException::class, 'Options::chroot');
            } finally {
                unlink($file);
            }

            expect($this->wrapper->getDomPDF()->getBasePath())->toBe($this->presetBase)
                ->and($this->wrapper->getDomPDF()->getProtocol())->toBe('');
        });

        it('resolves assets next to the file and restores the preset base for the next HTML', function () {
            $this->wrapper->loadFile($this->directory . '/document.html');

            expect($this->wrapper->getDomPDF()->getBasePath())->toBe($this->directory . '/')
                ->and($this->wrapper->loadHTML('<p>html</p>')->getDomPDF()->getBasePath())->toBe($this->presetBase);
        });

        it('resolves a relative path against the base path', function () {
            $this->wrapper->setBasePath($this->directory);

            expect($this->wrapper->loadFile('document.html')->getDomPDF()->outputHtml())->toContain('From file');
        });

        it('keeps a protocol and base path the caller set', function () {
            $this->wrapper->setProtocol('file://')->setBasePath($this->directory . '/');

            expect($this->wrapper->loadFile('document.html')->getDomPDF()->outputHtml())->toContain('From file')
                ->and($this->wrapper->loadHTML('<p>html</p>')->getDomPDF()->getBasePath())->toBe($this->directory . '/');
        });

        it('keeps a base path set after the file for the next HTML', function () {
            $this->wrapper->loadFile($this->directory . '/document.html')->setBasePath('/custom/');

            expect($this->wrapper->loadHTML('<p>html</p>')->getDomPDF()->getBasePath())->toBe('/custom/');
        });
    });

    it('throws on an unknown method instead of ignoring it', function () {
        expect(fn () => app('dynamicpdf')->__call('setNoSuchOption', [300]))->toThrow(UnexpectedValueException::class);
    });

    it('keeps the ssl stream options scalar when the certificate policy is applied twice', function () {
        $wrapper = app('dynamicpdf');
        $wrapper->allowSelfSignedCertificates();
        $wrapper->allowSelfSignedCertificates();

        $ssl = stream_context_get_options($wrapper->getDomPDF()->getHttpContext())['ssl'];

        expect($ssl['verify_peer'])->toBeFalse()
            ->and($ssl['verify_peer_name'])->toBeFalse()
            ->and($ssl['allow_self_signed'])->toBeTrue();
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

    it('falls back to the configured orientation for a template with only a size', function () {
        config(['dompdf.options.default_paper_orientation' => 'landscape']);
        $this->createTemplate(['code' => 'acme::pdf.paper', 'size' => 'a5', 'orientation' => null]);

        expect(app('dynamicpdf')->loadTemplate('acme::pdf.paper')->getDomPDF()->getPaperOrientation())->toBe('landscape');
    });

    it('applies the template orientation on the default paper size when no size is set', function () {
        $this->createTemplate(['code' => 'acme::pdf.paper', 'size' => null, 'orientation' => 'landscape']);

        $dompdf = app('dynamicpdf')->loadTemplate('acme::pdf.paper')->getDomPDF();
        [, , $width, $height] = $dompdf->getPaperSize();

        expect($dompdf->getPaperOrientation())->toBe('landscape')
            ->and($width)->toBeGreaterThan($height);
    });

    it('renders an empty string for a record with null content_html', function (Closure $parse) {
        expect($parse($this))->toBe('');
    })->with([
        'template' => [fn (TestCase $test): string => app('dynamicpdf')->parseTemplate(tap($test->createTemplate(), fn (Template $template) => $template->content_html = null))],
        'layout' => [fn (TestCase $test): string => app('dynamicpdf')->parseLayout(tap($test->createLayout(), fn (Layout $layout) => $layout->content_html = null))],
    ]);
});
