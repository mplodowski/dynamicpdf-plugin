<?php

use Cms\Classes\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use October\Rain\Exception\ValidationException;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Twig\Environment;
use Twig\TwigFilter;

describe('Twig syntax', function () {
    afterEach(function () {
        Event::forget('cms.theme.getActiveTheme');
        Event::forget('cms.extendTwig');
        Theme::resetCache();
        app()->forgetInstance('twig.environment');
    });

    it('rejects template HTML with a Twig syntax error on the HTML field with its line', function () {
        try {
            $this->createTemplate(['content_html' => "<p>Hello</p>\n<p>{{ foo </p>"]);
            $this->fail('The template was saved.');
        } catch (ValidationException $e) {
            expect($e->getErrors()->get('content_html')[0] ?? '')->toContain('line 2');
        }
    });

    it('rejects layout HTML with a Twig syntax error on the HTML field with its line', function () {
        try {
            $this->createLayout(['content_html' => '<html><body>{% if %}{{ content_html }}</body></html>']);
            $this->fail('The layout was saved.');
        } catch (ValidationException $e) {
            expect($e->getErrors()->get('content_html')[0] ?? '')->toContain('line 1');
        }
    });

    it('accepts filters the CMS registers on the environment the PDF renders with', function () {
        Event::listen('cms.theme.getActiveTheme', fn (): string => 'demo');
        Event::listen('cms.extendTwig', function (Environment $twig): void {
            $twig->addFilter(new TwigFilter('pdf_test_money', fn ($value): string => (string) $value));
        });
        Theme::resetCache();

        $template = $this->createTemplate(['content_html' => "{{ 10|pdf_test_money }} {{ 'a.css'|theme }}"]);

        expect($template->exists)->toBeTrue();
    });

    it('rejects an invalid template sent through the backend form', function () {
        actingAsPdfManager();
        $template = $this->createTemplate();

        $this->saveTemplateForm($template->id, ['title' => 'Invoice', 'content_html' => '<p>{{ foo </p>'])
            ->assertStatus(422)
            ->assertSee('line 1', false);

        expect(DB::table('renatio_dynamicpdf_pdf_templates')->where('id', $template->id)->value('content_html'))->toBe('<p>Test content</p>');
    });

    it('shows a readable error naming the layout instead of throwing in the HTML preview', function () {
        actingAsPdfManager();
        $layout = $this->createLayout(['code' => 'acme.broken.layout']);
        $template = $this->createTemplate(['layout_id' => $layout->id]);
        DB::table('renatio_dynamicpdf_pdf_layouts')->where('id', $layout->id)->update(['content_html' => "<html>\n{{ content_html </html>"]);

        $content = (new Templates)->html($template->id)->getContent();

        expect($content)->toContain('acme.broken.layout')
            ->toContain('line 2')
            ->not->toContain('__string_template__');
    });

    it('shows a readable error instead of an exception in the PDF preview', function () {
        actingAsPdfManager();
        $layout = $this->createLayout(['code' => 'acme.broken.layout']);
        DB::table('renatio_dynamicpdf_pdf_layouts')->where('id', $layout->id)->update(['content_html' => '{{ content_html </html>']);
        $controller = new Layouts;

        expect($controller->previewpdf($layout->id))->toBeNull()
            ->and((fn () => $this->fatalError)->call($controller))->toContain('acme.broken.layout');
    });
});
