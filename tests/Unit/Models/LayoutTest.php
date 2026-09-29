<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use October\Rain\Exception\ApplicationException;
use October\Rain\Exception\ValidationException;
use Renatio\DynamicPDF\Models\Layout;

describe('Layout', function () {
    it('finds a layout by code', function () {
        $layout = $this->createLayout(['code' => 'test.find.layout']);

        expect(Layout::byCode('test.find.layout')->id)->toBe($layout->id);
    });

    it('throws when no layout has the code', function () {
        expect(fn () => Layout::byCode('non.existent.layout'))
            ->toThrow(ModelNotFoundException::class);
    });

    it('compiles LESS in content_css', function () {
        $layout = $this->createLayout(['content_css' => '@color: red; body { color: @color; }']);

        expect($layout->getCSS())->toContain('color: red');
    });

    it('returns an empty string when content_css is null', function () {
        $layout = $this->createLayout();
        $layout->content_css = null;

        expect($layout->getCSS())->toBe('');
    });

    describe('server-local files', function () {
        beforeEach(function () {
            $this->secret = tempnam(sys_get_temp_dir(), 'dpdf');
            file_put_contents($this->secret, 'body { color: MARKER_184; }');
        });

        afterEach(fn () => @unlink($this->secret));

        dataset('file reading LESS', [
            'inline import' => '@import (inline) "%s";',
            'less import' => '@import (less) "%s";',
            'import inside a media block' => '@media print { @import (inline) "%s"; }',
            'interpolated import' => '@file: "%s"; @import (inline) "@{file}";',
            'data-uri' => 'body { background: DATA-URI("text/plain", "%s"); }',
            'image-size inside a mixin' => '.m() { width: image-width("%s"); } body { .m(); }',
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

        it('keeps remote CSS imports for dompdf', function () {
            $layout = $this->createLayout(['content_css' => '@import url("https://fonts.googleapis.com/css?family=Roboto"); @w: 2px; p { border: @w solid; }']);

            expect($layout->getCSS())->toContain('@import url("https://fonts.googleapis.com/css?family=Roboto")')
                ->toContain('border: 2px solid');
        });
    });
});
