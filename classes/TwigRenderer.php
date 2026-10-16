<?php

namespace Renatio\DynamicPDF\Classes;

use Cms\Classes\Theme;
use Exception;
use System\Facades\System;
use Twig\Environment;
use Twig\Error\Error as TwigError;
use Twig\Error\SyntaxError;
use Twig\Source;

class TwigRenderer
{
    protected const SYSTEM_ENVIRONMENT = 'renatio.dynamicpdf.twig.sandboxed';

    protected ?Environment $environment = null;

    protected ?PDFTwigController $controller = null;

    protected ?string $theme = null;

    /**
     * Twig names a string template by a hash, so its errors are renamed after the record code.
     *
     * @param  array<string, mixed>  $data
     */
    public function render(?string $markup, array $data, string $name): string
    {
        if ($markup === null || $markup === '') {
            return '';
        }

        $twig = $this->environment();

        if ((new TwigSandbox)->enabled()) {
            SandboxWarnings::instance()->start($name);
            $data = (new TwigData)->convert($data);
        }

        try {
            return $this->whileControllerCurrent(fn (): string => $twig->createTemplate($markup)->render($data));
        } catch (TwigError $e) {
            if ($e->getSourceContext()?->getCode() === $markup && str_starts_with($e->getSourceContext()->getName(), '__string_template__')) {
                $e->setSourceContext(new Source($markup, $name));
            }

            throw $e;
        }
    }

    /**
     * Without a theme (console, queue) the CMS filters and tags are missing, so an unknown
     * name is let through rather than rejecting markup a front-end render accepts.
     *
     * @throws TwigError
     */
    public function checkSyntax(string $markup, string $name): void
    {
        $twig = $this->environment();

        try {
            $this->whileControllerCurrent(function () use ($twig, $markup, $name): string {
                $twig->parse($twig->tokenize(new Source($markup, $name)));

                return '';
            });
        } catch (SyntaxError $e) {
            if ($this->controller !== null || ! System::hasModule('Cms') || ! preg_match('/^Unknown ".+" (filter|function|test|tag)\./', $e->getRawMessage())) {
                throw $e;
            }
        }
    }

    /**
     * @param  callable(): string  $render
     */
    protected function whileControllerCurrent(callable $render): string
    {
        return $this->controller ? $this->controller->whileCurrent($render) : $render();
    }

    protected function environment(): Environment
    {
        $theme = $this->activeTheme();

        if ($this->environment === null || $this->theme !== $theme?->getDirName()) {
            $this->theme = $theme?->getDirName();
            $this->controller = $theme ? $this->makeController($theme) : null;
            $this->environment = $this->controller
                ? (new TwigSandbox)->apply($this->controller->getTwig())
                : $this->systemEnvironment();
        }

        return $this->environment;
    }

    /**
     * A fresh twig.environment, built with the container's extenders, so the sandbox does not reach
     * the shared one behind Twig::parse() and .htm views. Shared by every render in the process.
     */
    protected function systemEnvironment(): Environment
    {
        $sandbox = new TwigSandbox;

        if (! $sandbox->enabled()) {
            return app('twig.environment');
        }

        $key = self::SYSTEM_ENVIRONMENT . '.' . md5(serialize([config('cms.safe_mode'), config('cms.security_policy_v1')]));

        if (! app()->bound($key)) {
            $shared = app()->resolved('twig.environment') ? app('twig.environment') : null;
            app()->forgetInstance('twig.environment');

            try {
                app()->instance($key, $sandbox->apply(app('twig.environment')));
            } finally {
                app()->forgetInstance('twig.environment');

                if ($shared) {
                    app()->instance('twig.environment', $shared);
                }
            }
        }

        return app($key);
    }

    protected function activeTheme(): ?Theme
    {
        if (! System::hasModule('Cms')) {
            return null;
        }

        try {
            return Theme::getActiveTheme();
        } catch (Exception) {
            return null;
        }
    }

    protected function makeController(Theme $theme): ?PDFTwigController
    {
        try {
            return new PDFTwigController($theme);
        } catch (Exception) {
            return null;
        }
    }
}
