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
            $this->environment = $this->controller?->getTwig() ?? app('twig.environment');
        }

        return $this->environment;
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
