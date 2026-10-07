<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\PDF;
use Dompdf\CanvasFactory;
use Dompdf\Dompdf;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Models\File;
use Throwable;
use UnexpectedValueException;

/**
 * @method self loadView(string $view, array<string, mixed> $data = [], array<string, mixed> $mergeData = [], ?string $encoding = null)
 * @method self setPaper(string|float[] $paper, string $orientation = 'portrait')
 * @method self setOption(array<string, mixed>|string $attribute, mixed $value = null)
 * @method self addInfo(array<string, string> $info)
 * @method self setWarnings(bool $warnings)
 * @method self setDefaultFont(string $font)
 * @method self setBasePath(string $basePath)
 * @method self setBaseHost(string $baseHost)
 * @method self setProtocol(string $protocol)
 * @method self setHttpContext(resource|array<string, mixed> $httpContext)
 * @method self setCallbacks(array<string, mixed> $callbacks)
 * @method self setDpi(int $dpi)
 * @method self setIsPhpEnabled(bool $enabled)
 * @method self setIsRemoteEnabled(bool $enabled)
 * @method self setLogOutputFile(string $path)
 */
class PDFWrapper extends PDF
{
    protected ?PageNumbers $pageNumbers = null;

    protected bool $pageNumbersStamped = false;

    protected ?TwigRenderer $twig = null;

    protected bool $forBrowser = false;

    protected ?LocalFiles $localFiles = null;

    /** @var array{before: string, file: array{string, string, string}}|null */
    protected ?array $baseBeforeFile = null;

    public function __construct(Dompdf $dompdf, ConfigRepository $config, Filesystem $files, ViewFactory $view)
    {
        parent::__construct($dompdf, $config, $files, $view);

        $this->applyCertificatePolicy();
        $this->ensureFontDir();
    }

    /**
     * Copies are kept until the wrapper is gone, because a second render() (setEncryption()
     * calls it) reads the background image again.
     */
    public function __destruct()
    {
        $this->localFiles?->delete();
    }

    /**
     * Renders HTML for a browser: |pdfasset and background_img give URLs instead of local paths.
     */
    public function forBrowser(bool $forBrowser = true): self
    {
        $this->forBrowser = $forBrowser;

        return $this;
    }

    public function isForBrowser(): bool
    {
        return $this->forBrowser;
    }

    public function localPath(File $file): string
    {
        return ($this->localFiles ??= new LocalFiles)->path($file);
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
        string $text = PageNumbers::DEFAULT_TEXT,
        string $position = PageNumbers::DEFAULT_POSITION,
        float $size = PageNumbers::DEFAULT_SIZE,
        ?string $font = null,
        float $margin = PageNumbers::DEFAULT_MARGIN,
        array $color = [0, 0, 0],
    ): self {
        $this->pageNumbers = new PageNumbers($text, $position, $size, $font, $margin, $color);

        return $this;
    }

    public function loadHTML(string $string, ?string $encoding = null): self
    {
        $this->restoreBaseBeforeFile();
        $this->forgetPreviousDocument();
        parent::loadHTML($string, $encoding);
        $this->resetCanvas();

        return $this;
    }

    /**
     * dompdf derives the protocol and base path from the file only while all three are empty,
     * and the service provider presets the base path, so that preset is cleared for the load.
     */
    public function loadFile(string $file): self
    {
        $this->restoreBaseBeforeFile();
        $this->forgetPreviousDocument();

        if ($this->dompdf->getProtocol() === '' && $this->dompdf->getBaseHost() === '') {
            $basePath = $this->dompdf->getBasePath();
            $this->dompdf->setBasePath('');

            try {
                parent::loadFile($basePath !== '' && $this->isRelativePath($file) ? rtrim($basePath, '/\\') . '/' . $file : $file);
            } catch (Throwable $e) {
                $this->restorePresetBase($basePath);

                throw $e;
            }

            $this->baseBeforeFile = ['before' => $basePath, 'file' => $this->currentBase()];
        } else {
            parent::loadFile($file);
        }

        $this->resetCanvas();

        return $this;
    }

    protected function forgetPreviousDocument(): void
    {
        $this->pageNumbers = null;
        $this->pageNumbersStamped = false;
    }

    protected function restoreBaseBeforeFile(): void
    {
        if ($this->baseBeforeFile !== null && $this->currentBase() === $this->baseBeforeFile['file']) {
            $this->restorePresetBase($this->baseBeforeFile['before']);
        }

        $this->baseBeforeFile = null;
    }

    protected function restorePresetBase(string $basePath): void
    {
        $this->dompdf->setProtocol('')->setBaseHost('')->setBasePath($basePath);
    }

    protected function isRelativePath(string $file): bool
    {
        return ! str_contains($file, '://') && ! preg_match('~^([/\\\\]|[a-z]:[/\\\\])~i', $file);
    }

    /**
     * @return array{string, string, string}
     */
    protected function currentBase(): array
    {
        return [$this->dompdf->getProtocol(), $this->dompdf->getBaseHost(), $this->dompdf->getBasePath()];
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
            $this->pageNumbers->stamp($this->dompdf);
            $this->pageNumbersStamped = true;
        }
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
        $this->localFiles?->delete();

        $template = Template::byCode($code);
        $template->fillFromLocalizedView($locale);

        if ($layout !== null) {
            $template->setRelation('layout', Layout::byCode($layout));
        }

        $template->layout?->fillFromLocalizedView($locale);

        $html = (new LocaleScope($locale))->render(
            [$template, $template->layout],
            $data,
            fn (array $data): string => $this->parseTemplate($template, $data),
        );

        $this->loadHTML($html, $encoding);
        $this->pageNumbers = $template->layout?->pageNumbers($locale);

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
        $this->localFiles?->delete();

        $layout = Layout::byCode($code);

        $layout->fillFromLocalizedView($locale);

        $html = (new LocaleScope($locale))->render(
            [$layout],
            $data,
            fn (array $data): string => $this->parseLayout($layout, $data),
        );

        $this->loadHTML($html, $encoding);
        $this->pageNumbers = $layout->pageNumbers($locale);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseTemplate(Template $template, array $data = []): string
    {
        return $this->renderer()->template($this, $template, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseLayout(Layout $layout, array $data = []): string
    {
        return $this->renderer()->layout($this, $layout, $data);
    }

    public function allowRemoteApplicationAssets(): self
    {
        (new RemoteAssetPolicy)->allowApplicationAssets($this->dompdf);

        return $this;
    }

    public function allowSelfSignedCertificates(): self
    {
        (new RemoteAssetPolicy)->allowSelfSignedCertificates($this->dompdf);

        return $this;
    }

    protected function twig(): TwigRenderer
    {
        return $this->twig ??= new TwigRenderer;
    }

    protected function renderer(): TemplateRenderer
    {
        return new TemplateRenderer($this->twig());
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
