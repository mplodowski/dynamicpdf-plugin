<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\PDF;
use Cms\Classes\Controller;
use Cms\Classes\Theme;
use Dompdf\Dompdf;
use Exception;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\SiteManager;
use System\Facades\System;
use System\Models\File;
use System\Models\SiteDefinition;
use Twig\Environment;
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
     * Page numbers are stamped on the canvas after rendering, so no inline PHP has to be
     * enabled for them. {PAGE_NUM} and {PAGE_COUNT} are replaced by dompdf on every page.
     *
     * @param  array<int, float>  $color  RGB components between 0 and 1
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
        $this->pageNumbersStamped = false;
        parent::loadHTML($string, $encoding);

        return $this;
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

    /**
     * The rendered document as a file record ready to be attached to a model.
     */
    public function toFile(string $filename = 'document.pdf'): File
    {
        $file = new File;
        $file->fromData($this->output(), $filename);

        return $file;
    }

    /**
     * @param  array<int, string>  $permissions  dompdf permission names, for example ['print']
     */
    public function encrypt(string $password, string $ownerPassword = '', array $permissions = []): self
    {
        $this->setEncryption($password, $ownerPassword, $permissions);

        return $this;
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        }

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
     * @param  string|null  $layout  code of a layout to render with instead of the stored one
     * @param  string|null  $locale  language to render in, restored afterwards
     */
    public function loadTemplate(string $code, array $data = [], ?string $encoding = null, ?string $layout = null, ?string $locale = null): self
    {
        $template = Template::byCode($code);

        if ($layout !== null) {
            $template->setRelation('layout', Layout::byCode($layout));
        }

        $data = $this->withLocaleVariable($data, $locale);

        $this->loadHTML(
            $this->inLocale($locale, fn (): string => $this->parseTemplate($template, $data)),
            $encoding,
        );

        if ($template->size) {
            $this->setPaper($template->size, $template->orientation ?? 'portrait');
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string|null  $locale  language to render in, restored afterwards
     */
    public function loadLayout(string $code, array $data = [], ?string $encoding = null, ?string $locale = null): self
    {
        $layout = Layout::byCode($code);
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
        $html = $this->parseMarkup($template->content_html, $data);

        if (! $template->layout) {
            return $html;
        }

        return $this->parseLayout(
            $template->layout,
            array_merge(['content_html' => $html], $data),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseLayout(Layout $layout, array $data = []): string
    {
        return $this->parseMarkup(
            $layout->content_html,
            $this->layoutData($layout, $data),
        );
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
     * The translator and Carbon read the application locale, so it is switched for the
     * parse only and always put back, also when the parse throws.
     *
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
     * @param  array<string, mixed>  $data
     */
    protected function parseMarkup(?string $markup, array $data): string
    {
        if ($markup === null || $markup === '') {
            return '';
        }

        return $this->twig()->createTemplate($markup)->render($data);
    }

    /**
     * The CMS environment adds theme partials and content on top of the system one, so it
     * is used whenever the module is installed and a usable theme is active. Looking the
     * theme up can itself throw (no theme configured, a locked theme), which must not stop
     * a PDF from rendering.
     */
    protected function twig(): Environment
    {
        if (System::hasModule('Cms')) {
            try {
                if (Theme::getActiveTheme() !== null) {
                    return (new Controller)->getTwig();
                }
            } catch (Exception) {
            }
        }

        return app('twig.environment');
    }

    /**
     * Remote resources stay limited to the hosts from the dompdf configuration plus the
     * application and site hosts, so a template cannot make the server fetch internal
     * addresses. The request host is deliberately not consulted: it is client-controlled.
     * Local files stay under the asset directories unless the configuration names its own
     * chroot, so a template cannot embed .env or the logs through file://.
     */
    public function allowRemoteApplicationAssets(): self
    {
        $options = $this->dompdf->getOptions();

        $hosts = array_merge($options->getAllowedRemoteHosts() ?: [], $this->applicationHosts());

        $options->setIsRemoteEnabled(true)->setAllowedRemoteHosts(array_values(array_unique($hosts)));

        $chroot = $options->getChroot();

        if (count($chroot) === 1 && realpath((string) $chroot[0]) === realpath(base_path())) {
            $options->setChroot(self::previewChroot());
        }

        return $this;
    }

    /**
     * The directories a template may legitimately embed files from: what October mirrors as
     * public plus public uploads and the media and resize caches. Configuration, logs and
     * protected uploads stay out. Without a public/ folder public_path() is the project root
     * and is dropped, or the restriction would allow everything again.
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
            fn ($url): string => mb_strtolower((string) parse_url((string) $url, PHP_URL_HOST)),
            $urls,
        )));
    }

    public function allowSelfSignedCertificates(): self
    {
        $current = $this->dompdf->getHttpContext();

        $context = stream_context_create(array_merge_recursive(
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
