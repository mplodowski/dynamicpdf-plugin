<?php

use Cms\Classes\Theme;
use Illuminate\Support\Facades\Event;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

describe('Twig environment', function () {
    afterEach(function () {
        Event::forget('cms.theme.getActiveTheme');
        Theme::resetCache();
        app()->forgetInstance('twig.environment');
    });

    it('renders through the CMS environment when a theme is active', function () {
        Event::listen('cms.theme.getActiveTheme', fn (): string => 'demo');
        Theme::resetCache();
        $template = $this->createTemplate(['content_html' => "{{ 'assets/css/theme.css'|theme }}"]);

        expect(app('dynamicpdf')->parseTemplate($template))->toContain('themes/demo/assets/css/theme.css');
    });

    it('renders through the system environment when no theme is active', function () {
        $twig = new Environment(new ArrayLoader);
        $twig->addFunction(new TwigFunction('probe', fn (): string => 'system twig'));
        app()->instance('twig.environment', $twig);
        $template = $this->createTemplate(['content_html' => '{{ probe() }}']);

        expect(app('dynamicpdf')->parseTemplate($template))->toBe('system twig');
    });

    it('falls back to the system environment when the theme lookup throws', function () {
        config(['cms.active_theme' => '']);
        Theme::resetCache();
        $template = $this->createTemplate(['content_html' => "{{ '**bold**'|md }}"]);

        expect(app('dynamicpdf')->parseTemplate($template))->toBe('<p><strong>bold</strong></p>');
    });

    it('reports a CMS rendering error instead of retrying with the system environment', function () {
        Event::listen('cms.theme.getActiveTheme', fn (): string => 'demo');
        Theme::resetCache();
        $template = $this->createTemplate(['content_html' => '<p>{{ name </p>']);

        expect(fn () => app('dynamicpdf')->parseTemplate($template))->toThrow(SyntaxError::class);
    });
});
