<?php

use Illuminate\Support\Facades\DB;
use October\Rain\Exception\ValidationException;
use Renatio\DynamicPDF\Classes\PDFWrapper;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Layout;

describe('Layout page numbers', function () {
    beforeEach(function () {
        app()->setLocale('en');

        $this->render = fn (Closure $load): string => $load(app('dynamicpdf')->setOption(['defaultFont' => 'serif', 'pdfBackend' => 'CPDF']))
            ->output(['compress' => 0]);

        $this->layoutWith = fn (array $attributes = []): Layout => $this->createLayout(array_merge([
            'code' => 'acme::pdf.layouts.' . uniqid(),
            'content_html' => '<html><body>{{ content_html|raw }}</body></html>',
        ], $attributes));

        $this->templateOn = fn (Layout $layout): string => $this->createTemplate([
            'code' => 'acme::pdf.' . uniqid(),
            'layout_id' => $layout->id,
            'content_html' => '<p>one</p><p style="page-break-before: always;">two</p>',
        ])->code;
    });

    it('falls back to DejaVu Sans for a font the document does not load', function () {
        $code = ($this->templateOn)(($this->layoutWith)(['page_numbers' => 'bottom-center', 'page_numbers_font' => 'No Such Family']));

        expect(($this->render)(fn (PDFWrapper $pdf) => $pdf->loadTemplate($code)))->toContain('/BaseFont /DejaVuSans');
    });

    it('stamps letters outside Latin-1 with DejaVu Sans', function () {
        $code = ($this->templateOn)(($this->layoutWith)(['page_numbers' => 'bottom-center', 'page_numbers_text' => 'Страница {PAGE_NUM}']));

        expect(($this->render)(fn (PDFWrapper $pdf) => $pdf->loadTemplate($code)))->toContain('/BaseFont /DejaVuSans');
    });

    it('ignores an unknown position from a view file instead of failing the render', function () {
        $layout = ($this->layoutWith)();
        DB::table('renatio_dynamicpdf_pdf_layouts')->where('id', $layout->id)->update(['page_numbers' => 'bottom']);
        $code = ($this->templateOn)($layout);

        expect(($this->render)(fn (PDFWrapper $pdf) => $pdf->loadTemplate($code)))->toStartWith('%PDF');
    });
    it('stamps the layout page numbers on a template rendered from code', function () {
        $code = ($this->templateOn)(($this->layoutWith)(['page_numbers' => 'bottom-right', 'page_numbers_text' => 'Sheet {PAGE_NUM}/{PAGE_COUNT}']));

        expect(($this->render)(fn (PDFWrapper $pdf) => $pdf->loadTemplate($code)))
            ->toContain('Sheet 1/2')
            ->toContain('Sheet 2/2');
    });

    it('stamps nothing when the layout has page numbers off', function () {
        $code = ($this->templateOn)(($this->layoutWith)(['page_numbers_text' => 'Sheet {PAGE_NUM}/{PAGE_COUNT}']));

        expect(($this->render)(fn (PDFWrapper $pdf) => $pdf->loadTemplate($code)))->not->toContain('Sheet 1/2');
    });

    it('uses the page numbers of a layout given for the render', function () {
        $code = ($this->templateOn)(($this->layoutWith)());
        $other = ($this->layoutWith)(['page_numbers' => 'top-left', 'page_numbers_text' => 'Other {PAGE_NUM}']);

        expect(($this->render)(fn (PDFWrapper $pdf) => $pdf->loadTemplate($code, layout: $other->code)))->toContain('Other 2');
    });

    it('stamps the page numbers of a layout rendered alone', function () {
        $layout = ($this->layoutWith)([
            'page_numbers' => 'bottom-center',
            'page_numbers_text' => 'Layout {PAGE_NUM}',
            'content_html' => '<html><body><p>one</p><p style="page-break-before: always;">two</p></body></html>',
        ]);

        expect(($this->render)(fn (PDFWrapper $pdf) => $pdf->loadLayout($layout->code)))->toContain('Layout 2');
    });

    it('lets pageNumbers() called from code override the layout', function () {
        $code = ($this->templateOn)(($this->layoutWith)(['page_numbers' => 'bottom-center', 'page_numbers_text' => 'Layout {PAGE_NUM}']));

        expect(($this->render)(fn (PDFWrapper $pdf) => $pdf->loadTemplate($code)->pageNumbers('Code {PAGE_NUM}')))
            ->toContain('Code 2')
            ->not->toContain('Layout 2');
    });

    it('translates the default text into the locale of the render', function () {
        $code = ($this->templateOn)(($this->layoutWith)(['page_numbers' => 'bottom-center']));

        expect(($this->render)(fn (PDFWrapper $pdf) => $pdf->loadTemplate($code, locale: 'pl')))->toContain('Strona 2 z 2')
            ->and(($this->render)(fn (PDFWrapper $pdf) => $pdf->loadTemplate($code)))->toContain('Page 2 of 2');
    });

    it('rejects an unknown position or color', function (string $attribute, string $value) {
        expect(fn () => ($this->layoutWith)(['page_numbers' => 'bottom-center', $attribute => $value]))
            ->toThrow(ValidationException::class);
    })->with([
        'position' => ['page_numbers', 'middle'],
        'color' => ['page_numbers_color', 'red'],
    ]);

    describe('from a view', function () {
        beforeEach(function () {
            $this->registerViewLayouts('acme', [
                'layouts/numbered' => "name = \"Numbered\"\npageNumbers = \"top-right\"\npageNumbersText = \"{PAGE_NUM}\"\npageNumbersMargin = 0\npageNumbersColor = \"#ff0000\"\n==\n<html><body>{{ content_html|raw }}</body></html>",
            ]);

            (new SyncTemplates)->handle();
        });

        it('reads the page numbers from the view settings', function () {
            $layout = $this->findLayout('acme::pdf.layouts.numbered');

            expect($layout->only(['page_numbers', 'page_numbers_text', 'page_numbers_margin', 'page_numbers_color']))->toEqual([
                'page_numbers' => 'top-right',
                'page_numbers_text' => '{PAGE_NUM}',
                'page_numbers_margin' => 0,
                'page_numbers_color' => '#ff0000',
            ]);
        });

        it('detaches the layout from its view when its page numbers are edited in the backend', function () {
            $layout = $this->findLayout('acme::pdf.layouts.numbered');
            actingAsPdfManager();

            $this->saveLayoutForm($layout->id, [
                'name' => $layout->name,
                'content_html' => $layout->content_html,
                'content_css' => '',
                'page_numbers' => 'bottom-left',
            ])->assertOk();

            $stored = $this->findLayout('acme::pdf.layouts.numbered');

            expect($stored->is_locked)->toBeFalse()
                ->and($stored->page_numbers)->toBe('bottom-left');
        });
    });
});

describe('Layout page numbers form', function () {
    beforeEach(function () {
        $this->registerViewLayouts('acme', [
            'layouts/numbered' => "name = \"Numbered\"\npageNumbers = \"bottom-center\"\npageNumbersColor = \"#6b7280\"\npageNumbersSize = 9\n==\n<html><body>{{ content_html|raw }}</body></html>",
        ]);
        (new SyncTemplates)->handle();
        actingAsPdfManager();
    });

    it('keeps the layout following its view when the form posts the same values in another notation', function () {
        $layout = $this->findLayout('acme::pdf.layouts.numbered');

        $this->saveLayoutForm($layout->id, [
            'name' => 'Numbered',
            'content_html' => $layout->content_html,
            'content_css' => '',
            'page_numbers' => 'bottom-center',
            'page_numbers_color' => '#6B7280',
            'page_numbers_size' => '9.0',
        ])->assertOk();

        expect($this->findLayout('acme::pdf.layouts.numbered')->is_locked)->toBeTrue();
    });
});
