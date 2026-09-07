<?php

use Twig\Error\SyntaxError;

describe('Locale per render', function () {
    it('renders a template in the given locale and restores the previous one', function () {
        app()->setLocale('en');
        $this->createTemplate(['code' => 'acme::pdf.invoice', 'content_html' => "{{ 'backend::lang.form.save'|trans }} {{ locale }}"]);

        $wrapper = app('dynamicpdf')->loadTemplate('acme::pdf.invoice', locale: 'pl');

        expect($wrapper->getDomPDF()->outputHtml())->toContain('Zapisz pl')
            ->and(app()->getLocale())->toBe('en');
    });

    it('restores the previous locale when rendering fails', function () {
        app()->setLocale('en');
        $this->createTemplate(['code' => 'acme::pdf.broken', 'content_html' => '{{ name']);

        expect(fn () => app('dynamicpdf')->loadTemplate('acme::pdf.broken', locale: 'pl'))->toThrow(SyntaxError::class)
            ->and(app()->getLocale())->toBe('en');
    });

    it('renders a layout in the given locale', function () {
        app()->setLocale('en');
        $this->createLayout(['code' => 'acme::pdf.layouts.default', 'content_html' => "<html><body>{{ 'backend::lang.form.save'|trans }}</body></html>"]);

        $wrapper = app('dynamicpdf')->loadLayout('acme::pdf.layouts.default', locale: 'de');

        expect($wrapper->getDomPDF()->outputHtml())->toContain('Speichern')
            ->and(app()->getLocale())->toBe('en');
    });
});
