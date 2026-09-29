<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\PDF;
use Closure;
use Cms\Classes\Theme;
use Dompdf\CanvasFactory;
use Dompdf\Dompdf;
use Exception;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\SiteManager;
use System\Facades\System;
use System\Models\File;
use System\Models\SiteDefinition;
use Twig\Environment;
use Twig\Error\Error as TwigError;
use Twig\Error\SyntaxError;
use Twig\Source;
use UnexpectedValueException;

/**
 * @method self setDpi(int $dpi)
 * @method self setIsPhpEnabled(bool $enabled)
 * @method self setIsRemoteEnabled(bool $enabled)
 * @method self setLogOutputFile(string $path)
 */
class PDFWrapper extends PDF
{
    public const PAGE_NUMBERS_POSITIONS = ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'];

    /** @var array{text: string, position: string, size: float, font: string|null, margin: float, color: array<int, float>}|null */
    protected ?array $pageNumbers = null;

    protected bool $pageNumbersStamped = false;

    protected ?Environment $twigEnvironment = null;

    protected ?PDFTwigController $twigController = null;

    protected ?string $twigTheme = null;

    public function __construct(Dompdf $dompdf, ConfigRepository $config, Filesystem $files, ViewFactory $view)
    {
        parent::__construct($dompdf, $config, $files, $view);

        $this->applyCertificatePolicy();
        $this->ensureFontDir();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function setOptions(array $options, bool $mergeWithDefaults = false): self
    {
        parent::setOptions($options, $mergeWithDefaults);

        $this->applyCertificatePolicy();

        return $this;
    }

    /**
     * @param  array<int, float>  $color
     */
    public function pageNumbers(
        string $text = 'Page {PAGE_NUM} of {PAGE_COUNT}',
        string $position = 'bottom-center',
        float $size = 9,
        ?string $font = null,
        float $margin = 20,
        array $color = [0, 0, 0],
    ): self {
        if (! in_array($position, self::PAGE_NUMBERS_POSITIONS, true)) {
            throw new InvalidArgumentException("Unknown page numbers position [{$position}].");
        }

        if (count($color) !== 3 || array_filter($color, fn (float $c): bool => $c < 0 || $c > 1) !== []) {
            throw new InvalidArgumentException('Page numbers color must be three RGB components between 0 and 1.');
        }

        $this->pageNumbers = compact('text', 'position', 'size', 'font', 'margin', 'color');

        return $this;
    }

    public function loadHTML(string $string, ?string $encoding = null): self
    {
        $this->pageNumbers = null;
        $this->pageNumbersStamped = false;
        parent::loadHTML($string, $encoding);
        $this->resetCanvas();

        return $this;
    }

    /**
     * dompdf rebuilds the canvas only when the paper size changes, so page text stamped on an
     * earlier document would reappear on the next one rendered by the same instance.
     */
    protected function resetCanvas(): void
    {
        $canvas = CanvasFactory::get_instance($this->dompdf, $this->dompdf->getPaperSize(), $this->dompdf->getPaperOrientation());

        $this->dompdf->setCanvas($canvas);
        $this->dompdf->getFontMetrics()->setCanvas($canvas);
    }

    /**
     * Stamping appends to the page streams, so a second render() (setEncryption() calls it
     * unguarded) must not stamp again.
     */
    public function render(): void
    {
        parent::render();

        if ($this->pageNumbers !== null && ! $this->pageNumbersStamped) {
            $this->stampPageNumbers($this->pageNumbers);
            $this->pageNumbersStamped = true;
        }
    }

    /**
     * @param  array{text: string, position: string, size: float, font: string|null, margin: float, color: array<int, float>}  $numbers
     */
    protected function stampPageNumbers(array $numbers): void
    {
        $canvas = $this->dompdf->getCanvas();
        $metrics = $this->dompdf->getFontMetrics();
        $font = $metrics->getFont($numbers['font']);

        if ($font === null) {
            throw new InvalidArgumentException("Font [{$numbers['font']}] is not available in the rendered document.");
        }

        $pages = (string) $canvas->get_page_count();
        // One page_text() call serves every page, so the widest text (the last page) sets the position.
        $sample = str_replace(['{PAGE_NUM}', '{PAGE_COUNT}'], [$pages, $pages], $numbers['text']);
        $width = $metrics->getTextWidth($sample, $font, $numbers['size']);
        $height = $metrics->getFontHeight($font, $numbers['size']);
        [$vertical, $horizontal] = explode('-', $numbers['position']);

        $x = match ($horizontal) {
            'left' => $numbers['margin'],
            'center' => ($canvas->get_width() - $width) / 2,
            default => $canvas->get_width() - $numbers['margin'] - $width,
        };
        $y = $vertical === 'top' ? $numbers['margin'] : $canvas->get_height() - $numbers['margin'] - $height;

        $canvas->page_text($x, $y, $numbers['text'], $font, $numbers['size'], $numbers['color']);
    }

    public function toFile(string $filename = 'document.pdf', bool $public = true): File
    {
        $file = new File;
        $file->setAttribute('is_public', $public);
        $file->fromData($this->output(), $filename);

        return $file;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function encrypt(string $password, string $ownerPassword = '', array $permissions = []): self
    {
        $this->setEncryption($password, $ownerPassword, $permissions);

        return $this;
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this->dompdf, $method)) {
            $return = $this->dompdf->$method(...$parameters);

            return $return === $this->dompdf ? $this : $return;
        }

        $options = $this->dompdf->getOptions();

        if (! method_exists($options, $method)) {
            throw new UnexpectedValueException("Method [{$method}] does not exist on PDF instance.");
        }

        $options->$method(...$parameters);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function loadTemplate(string $code, array $data = [], ?string $encoding = null, ?string $layout = null, ?string $locale = null): self
    {
        $template = Template::byCode($code);
        $template->fillFromLocalizedView($locale);

        if ($layout !== null) {
            $template->setRelation('layout', Layout::byCode($layout));
        }

        $template->layout?->fillFromLocalizedView($locale);

        $this->applyTranslateContext($template, $locale);
        $this->applyTranslateContext($template->layout, $locale);

        $data = $this->withLocaleVariable($data, $locale);

        $this->loadHTML(
            $this->inLocale($locale, fn (): string => $this->parseTemplate($template, $data)),
            $encoding,
        );

        if ($template->size || $template->orientation) {
            $options = $this->dompdf->getOptions();

            $this->setPaper($template->size ?: $options->getDefaultPaperSize(), $template->orientation ?: $options->getDefaultPaperOrientation());
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function loadLayout(string $code, array $data = [], ?string $encoding = null, ?string $locale = null): self
    {
        $layout = Layout::byCode($code);

        $layout->fillFromLocalizedView($locale);

        $this->applyTranslateContext($layout, $locale);

        $data = $this->withLocaleVariable($data, $locale);

        $this->loadHTML(
            $this->inLocale($locale, fn (): string => $this->parseLayout($layout, $data)),
            $encoding,
        );

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseTemplate(Template $template, array $data = []): string
    {
        return $this->renderWithEvents($template, $data, function (array $data) use ($template): string {
            $html = $this->parseMarkup($template->content_html, $data, (string) $template->code);

            if (! $template->layout) {
                return $html;
            }

            return $this->renderLayout(
                $template->layout,
                array_merge(['content_html' => $html], $data),
            );
        });
    }

    public const RESERVED_VARIABLES = ['content_html', 'css', 'background_img', 'locale'];

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(array<string, mixed>): string  $render
     */
    protected function renderWithEvents(Template|Layout $model, array $data, callable $render): string
    {
        $data = array_merge($this->withoutReserved($this->registeredVariables()), $data);

        foreach (Event::fire(Events::BEFORE_RENDER, [$this, $model, $data]) ?? [] as $extra) {
            if (is_array($extra)) {
                $data = array_merge($data, $this->withoutReserved($extra));
            }
        }

        $html = $render($data);

        foreach (Event::fire(Events::AFTER_RENDER, [$this, $model, $html]) ?? [] as $replacement) {
            if (is_string($replacement)) {
                $html = $replacement;
            }
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    protected function withoutReserved(array $variables): array
    {
        return array_diff_key($variables, array_flip(self::RESERVED_VARIABLES));
    }

    /**
     * @return array<string, mixed>
     */
    protected function registeredVariables(): array
    {
        return array_map(
            fn (mixed $value): mixed => $value instanceof Closure ? $value() : $value,
            PDFManager::instance()->listRegisteredVariables(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseLayout(Layout $layout, array $data = []): string
    {
        return $this->renderWithEvents($layout, $data, fn (array $data): string => $this->renderLayout($layout, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function renderLayout(Layout $layout, array $data): string
    {
        return $this->parseMarkup(
            $layout->content_html,
            $this->layoutData($layout, $data),
            (string) $layout->code,
        );
    }

    protected function applyTranslateContext(Template|Layout|null $model, ?string $locale): void
    {
        if ($locale === null || $locale === '' || $model === null || ! $model->exists || ! $model->isTranslatableEnabled()) {
            return;
        }

        foreach (SiteManager::instance()->getLocaleKeyChain($locale) as $localeKey) {
            if ($model->hasTranslations($localeKey)) {
                $model->setLocale($localeKey);

                return;
            }
        }

        $model->setLocale($locale);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withLocaleVariable(array $data, ?string $locale): array
    {
        return array_merge(['locale' => $locale === null || $locale === '' ? app()->getLocale() : $locale], $data);
    }

    /**
     * @param  callable(): string  $render
     */
    protected function inLocale(?string $locale, callable $render): string
    {
        if ($locale === null || $locale === '') {
            return $render();
        }

        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return $render();
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function layoutData(Layout $layout, array $data): array
    {
        return array_merge([
            'background_img' => $layout->background_img?->getPath(),
            'css' => $layout->getCSS(),
        ], $data);
    }

    /**
     * Without a theme (console, queue) the CMS filters and tags are missing, so an unknown
     * name is let through rather than rejecting markup a front-end render accepts.
     *
     * @throws TwigError
     */
    public function checkSyntax(string $markup, string $name): void
    {
        $twig = $this->twig();

        try {
            $this->whileTwigControllerCurrent(function () use ($twig, $markup, $name): string {
                $twig->parse($twig->tokenize(new Source($markup, $name)));

                return '';
            });
        } catch (SyntaxError $e) {
            if ($this->twigController !== null || ! System::hasModule('Cms') || ! preg_match('/^Unknown ".+" (filter|function|test|tag)\./', $e->getRawMessage())) {
                throw $e;
            }
        }
    }

    /**
     * Twig names a string template by a hash, so its errors are renamed after the record code.
     *
     * @param  array<string, mixed>  $data
     */
    protected function parseMarkup(?string $markup, array $data, string $name): string
    {
        if ($markup === null || $markup === '') {
            return '';
        }

        $twig = $this->twig();

        try {
            return $this->whileTwigControllerCurrent(fn (): string => $twig->createTemplate($markup)->render($data));
        } catch (TwigError $e) {
            if ($e->getSourceContext()?->getCode() === $markup && str_starts_with($e->getSourceContext()->getName(), '__string_template__')) {
                $e->setSourceContext(new Source($markup, $name));
            }

            throw $e;
        }
    }

    /**
     * @param  callable(): string  $render
     */
    protected function whileTwigControllerCurrent(callable $render): string
    {
        return $this->twigController ? $this->twigController->whileCurrent($render) : $render();
    }

    protected function twig(): Environment
    {
        $theme = $this->activeTheme();

        if ($this->twigEnvironment === null || $this->twigTheme !== $theme?->getDirName()) {
            $this->twigTheme = $theme?->getDirName();
            $this->twigController = $theme ? $this->makeTwigController($theme) : null;
            $this->twigEnvironment = $this->twigController?->getTwig() ?? app('twig.environment');
        }

        return $this->twigEnvironment;
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

    protected function makeTwigController(Theme $theme): ?PDFTwigController
    {
        try {
            return new PDFTwigController($theme);
        } catch (Exception) {
            return null;
        }
    }

    public function allowRemoteApplicationAssets(): self
    {
        $options = $this->dompdf->getOptions();

        $hosts = array_values(array_unique(array_merge($options->getAllowedRemoteHosts() ?: [], $this->applicationHosts())));

        /** An empty allowed host list is ignored by dompdf, which would leave remote fetching unrestricted. */
        if ($hosts === []) {
            $options->setIsRemoteEnabled(false);
        } else {
            $options->setIsRemoteEnabled(true)->setAllowedRemoteHosts($hosts);
        }

        $chroot = $options->getChroot();

        if (count($chroot) === 1 && realpath((string) $chroot[0]) === realpath(base_path())) {
            $options->setChroot(self::previewChroot());
        }

        return $this;
    }

    /**
     * Without a public/ folder public_path() is the project root, which would allow everything.
     *
     * @return array<int, string>
     */
    protected static function previewChroot(): array
    {
        $base = realpath(base_path());

        $paths = [
            public_path(),
            plugins_path(),
            themes_path(),
            base_path('modules'),
            base_path('app'),
            storage_path('app/uploads/public'),
            storage_path('app/public'),
            storage_path('app/media'),
            storage_path('app/resources'),
            storage_path('temp/public'),
        ];

        return array_values(array_filter($paths, fn (string $path): bool => realpath($path) !== $base));
    }

    /**
     * @return array<int, string>
     */
    protected function applicationHosts(): array
    {
        $urls = SiteManager::instance()->listEnabled()
            ->filter(fn (SiteDefinition $site): bool => (bool) $site->is_custom_url)
            ->pluck('app_url')
            ->push(config('app.url'))
            ->all();

        return array_values(array_filter(array_map(
            fn ($url): string => mb_strtolower(self::hostOf((string) $url)),
            $urls,
        )));
    }

    protected static function hostOf(string $url): string
    {
        return (string) (parse_url($url, PHP_URL_HOST) ?: parse_url('//' . ltrim($url, '/'), PHP_URL_HOST));
    }

    public function allowSelfSignedCertificates(): self
    {
        $current = $this->dompdf->getHttpContext();

        $context = stream_context_create(array_replace_recursive(
            is_resource($current) ? stream_context_get_options($current) : [],
            [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ],
        ));

        $this->dompdf->setHttpContext($context);

        return $this;
    }

    protected function ensureFontDir(): void
    {
        $fontDir = $this->dompdf->getOptions()->getFontDir();

        if ($fontDir && ! is_dir($fontDir) && ! @mkdir($fontDir, 0755, true) && ! is_dir($fontDir)) {
            Log::error("Renatio.DynamicPDF could not create the dompdf font directory {$fontDir}.");
        }
    }

    protected function applyCertificatePolicy(): void
    {
        if (config('renatio.dynamicpdf.allow_self_signed_certificates')) {
            $this->allowSelfSignedCertificates();
        }
    }
}
