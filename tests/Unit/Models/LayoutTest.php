<?php

use Illuminate\Support\Facades\DB;
use October\Rain\Exception\ApplicationException;
use October\Rain\Exception\ValidationException;

describe('Layout', function () {
    describe('server-local files', function () {
        beforeEach(function () {
            $this->documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
            $_SERVER['DOCUMENT_ROOT'] = '/';
            $this->secret = tempnam(sys_get_temp_dir(), 'dpdf');
            rename($this->secret, $this->secret .= '.svg');
            file_put_contents($this->secret, '<svg width="184" height="1"></svg>');
        });

        afterEach(function () {
            @unlink($this->secret);
            $_SERVER['DOCUMENT_ROOT'] = $this->documentRoot;
        });

        dataset('file reading LESS', [
            'inline import' => '@import (inline) "%s";',
            'less import' => '@import (less) "%s";',
            'import inside a media block' => '@media print { @import (inline) "%s"; }',
            'interpolated import' => '@file: "%s"; @import (inline) "@{file}";',
            'data-uri' => 'body { background: DATA-URI("text/plain", "%s"); }',
            'datauri alias' => 'body { background: datauri("text/plain", "%s"); }',
            'image-width inside a mixin' => '.m() { width: image-width("%s"); } body { .m(); }',
            'imagewidth alias' => 'body { width: imagewidth("%s"); }',
            'image-height' => 'body { height: image-height("%s"); }',
            'image-size' => 'body { background-size: image-size("%s"); }',
        ]);

        it('never compiles a stored layout that reads one', function (string $css) {
            $layout = $this->createLayout();
            DB::table('renatio_dynamicpdf_pdf_layouts')->where('id', $layout->id)->update(['content_css' => sprintf($css, $this->secret)]);

            expect(fn () => $layout->fresh()->getCSS())
                ->toThrow(ApplicationException::class, trans('renatio.dynamicpdf::lang.layout.css_reads_files'));
        })->with('file reading LESS');

        it('refuses to save a layout that reads one', function (string $css) {
            expect(fn () => $this->createLayout(['content_css' => sprintf($css, $this->secret)]))->toThrow(ValidationException::class);
        })->with('file reading LESS');

        it('leaves plain CSS imports to dompdf', function () {
            $layout = $this->createLayout(['content_css' => '@import url("https://fonts.googleapis.com/css?family=Roboto"); @import "fonts.css"; @w: 2px; p { border: @w solid; }']);

            expect($layout->getCSS())->toContain('@import url("https://fonts.googleapis.com/css?family=Roboto")')
                ->toContain('@import "fonts.css"')
                ->toContain('border: 2px solid');
        });
    });

    it('rejects LESS that does not compile with the line it failed on', function () {
        expect(fn () => $this->createLayout(['content_css' => "p {\n color: red;\n b: ;;{"]))
            ->toThrow(ValidationException::class, 'on line 3');
    });

    it('rejects a name or code longer than its column', function (string $field) {
        expect(fn () => $this->createLayout([$field => str_repeat('a', 256)]))
            ->toThrow(ValidationException::class, 'greater than 255');
    })->with(['name', 'code']);

    it('names the HTML field in the required message', function () {
        expect(fn () => $this->createLayout(['content_html' => '']))
            ->toThrow(ValidationException::class, 'The HTML field is required.');
    });

    it('names a single template blocking its deletion in the singular', function () {
        $layout = $this->createLayout();
        $this->createTemplate(['title' => 'Invoice', 'layout_id' => $layout->id]);

        expect(fn () => $layout->delete())
            ->toThrow(ApplicationException::class, 'because the template Invoice uses it. Assign it another layout first.');
    });
});
