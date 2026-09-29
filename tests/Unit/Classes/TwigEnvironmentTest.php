<?php

use Cms\Classes\Controller;
use Cms\Classes\Theme;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Renatio\DynamicPDF\Classes\PDFTwigController;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

describe('Twig environment', function () {
    afterEach(function () {
        Event::forget('cms.theme.getActiveTheme');
        Event::forget('cms.extendTwig');
        Theme::resetCache();
        app()->forgetInstance('twig.environment');
    });

    it('renders through the CMS environment when a theme is active', function () {
        Event::listen('cms.theme.getActiveTheme', fn (): string => 'demo');
        Theme::resetCache();
        $template = $this->createTemplate(['content_html' => "{{ 'assets/css/theme.css'|theme }}"]);

        expect(app('dynamicpdf')->parseTemplate($template))->toContain('themes/demo/assets/css/theme.css');
    });

    it('keeps the CMS controller of the current request', function () {
        Event::listen('cms.theme.getActiveTheme', fn (): string => 'demo');
        Theme::resetCache();
        $controller = new Controller;

        app('dynamicpdf')->parseTemplate($this->createTemplate());

        expect(Controller::getController())->toBe($controller);
    });

    it('acts as the CMS controller while rendering outside a front-end request', function () {
        Event::listen('cms.theme.getActiveTheme', fn (): string => 'demo');
        Theme::resetCache();
        (new ReflectionProperty(Controller::class, 'instance'))->setValue(null, null);
        Event::listen('cms.extendTwig', function (Environment $twig): void {
            $twig->addFunction(new TwigFunction('current_controller', fn (): string => get_debug_type(Controller::getController())));
        });
        $template = $this->createTemplate(['content_html' => '{{ current_controller() }}']);

        expect(app('dynamicpdf')->parseTemplate($template))->toBe(PDFTwigController::class)
            ->and(Controller::getController())->toBeNull();
    });

    it('rebuilds the CMS environment when a reused wrapper renders for another theme', function () {
        $theme = 'dynamicpdf-test-theme';
        File::makeDirectory(themes_path($theme));
        File::put(themes_path($theme . '/theme.yaml'), 'name: Test');
        $active = 'demo';
        Event::listen('cms.theme.getActiveTheme', function () use (&$active): string {
            return $active;
        });
        Theme::resetCache();
        $template = $this->createTemplate(['content_html' => "{{ 'style.css'|theme }}"]);
        $pdf = app('dynamicpdf');

        try {
            $first = $pdf->parseTemplate($template);
            $active = $theme;
            Theme::resetCache();

            expect($first)->toContain('themes/demo/style.css')
                ->and($pdf->parseTemplate($template))->toContain("themes/{$theme}/style.css");
        } finally {
            File::deleteDirectory(themes_path($theme));
        }
    });

    it('builds a single CMS environment for a template with a layout', function () {
        Event::listen('cms.theme.getActiveTheme', fn (): string => 'demo');
        Theme::resetCache();
        $layout = $this->createLayout();
        $this->createTemplate(['code' => 'acme::pdf.invoice', 'layout_id' => $layout->id]);
        $environments = 0;
        Event::listen('cms.extendTwig', function () use (&$environments) {
            $environments++;
        });

        app('dynamicpdf')->loadTemplate('acme::pdf.invoice');

        expect($environments)->toBe(1);
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
        $template = $this->createTemplate();
        $template->content_html = '<p>{{ name </p>';

        expect(fn () => app('dynamicpdf')->parseTemplate($template))->toThrow(SyntaxError::class);
    });
});
