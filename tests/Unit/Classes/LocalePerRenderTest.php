<?php

use Twig\Error\SyntaxError;

describe('Locale per render', function () {
    beforeEach(fn () => app()->setLocale('en'));

    it('renders a template in the given locale and restores the previous one', function () {
        $this->createTemplate(['code' => 'acme::pdf.invoice', 'content_html' => "{{ 'renatio.dynamicpdf::lang.templates.label'|trans }} {{ locale }}"]);

        $wrapper = app('dynamicpdf')->loadTemplate('acme::pdf.invoice', locale: 'pl');

        expect($wrapper->getDomPDF()->outputHtml())->toContain('Szablony pl')
            ->and(app()->getLocale())->toBe('en');
    });

    it('formats dates in the given locale', function () {
        $this->createTemplate(['code' => 'acme::pdf.dates', 'content_html' => "{{ date.translatedFormat('F') }}"]);

        $wrapper = app('dynamicpdf')->loadTemplate('acme::pdf.dates', ['date' => \Carbon\Carbon::create(2026, 3, 1)], locale: 'de');

        expect($wrapper->getDomPDF()->outputHtml())->toContain('März');
    });

    it('exposes the application locale and lets template data override it', function () {
        $this->createTemplate(['code' => 'acme::pdf.locale', 'content_html' => '{{ locale }}']);

        $default = app('dynamicpdf')->loadTemplate('acme::pdf.locale')->getDomPDF()->outputHtml();
        $overridden = app('dynamicpdf')->loadTemplate('acme::pdf.locale', ['locale' => 'custom'], locale: 'pl')->getDomPDF()->outputHtml();

        expect($default)->toContain('en')
            ->and($overridden)->toContain('custom');
    });

    it('restores the previous locale when parsing fails', function () {
        $this->createTemplate(['code' => 'acme::pdf.broken', 'content_html' => '{{ name']);

        expect(fn () => app('dynamicpdf')->loadTemplate('acme::pdf.broken', locale: 'pl'))->toThrow(SyntaxError::class)
            ->and(app()->getLocale())->toBe('en');
    });

    it('renders a layout in the given locale', function () {
        $this->createLayout(['code' => 'acme::pdf.layouts.default', 'content_html' => "<html><body>{{ 'renatio.dynamicpdf::lang.templates.label'|trans }}</body></html>"]);

        $wrapper = app('dynamicpdf')->loadLayout('acme::pdf.layouts.default', locale: 'de');

        expect($wrapper->getDomPDF()->outputHtml())->toContain('Vorlagen')
            ->and(app()->getLocale())->toBe('en');
    });
});
