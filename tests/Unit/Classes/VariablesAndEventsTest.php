<?php

use Illuminate\Support\Facades\Event;
use Renatio\DynamicPDF\Classes\Events;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFWrapper;
use Renatio\DynamicPDF\Models\Template;

describe('Global variables and events', function () {
    afterEach(function () {
        PDFManager::forgetInstance();
        Event::forget(Events::BEFORE_RENDER);
        Event::forget(Events::AFTER_RENDER);
    });

    it('exposes registered variables to templates and layouts, resolving closures lazily', function () {
        PDFManager::instance()->registerVariables(['company' => 'Acme', 'year' => fn (): string => '2026']);
        $layout = $this->createLayout(['content_html' => '<html><body>{{ company }} {{ content_html|raw }}</body></html>']);
        $template = $this->createTemplate(['content_html' => '<p>{{ year }} {{ company }}</p>', 'layout_id' => $layout->id]);

        expect(app('dynamicpdf')->parseTemplate($template))->toBe('<html><body>Acme <p>2026 Acme</p></body></html>');
    });

    it('lets render data override a registered variable', function () {
        PDFManager::instance()->registerVariables(['company' => 'Acme']);
        $template = $this->createTemplate(['content_html' => '{{ company }}']);

        expect(app('dynamicpdf')->parseTemplate($template, ['company' => 'Other']))->toBe('Other');
    });

    it('lets a beforeRender listener add data and an afterRender listener change the HTML', function () {
        Event::listen(Events::BEFORE_RENDER, fn (PDFWrapper $pdf, Template $template, array $data): array => ['stamp' => 'COPY']);
        Event::listen(Events::AFTER_RENDER, fn (PDFWrapper $pdf, Template $template, string $html): string => $html . '<!-- audited -->');
        $template = $this->createTemplate(['content_html' => '<p>{{ stamp }}</p>']);

        expect(app('dynamicpdf')->parseTemplate($template))->toBe('<p>COPY</p><!-- audited -->');
    });

    it('fires the events for a layout loaded on its own', function () {
        Event::listen(Events::BEFORE_RENDER, fn (): array => ['stamp' => 'LAYOUT']);
        $this->createLayout(['code' => 'acme::pdf.layouts.stamp', 'content_html' => '<html><body>{{ stamp }}</body></html>']);

        expect(app('dynamicpdf')->loadLayout('acme::pdf.layouts.stamp')->getDomPDF()->outputHtml())->toContain('LAYOUT');
    });
});
