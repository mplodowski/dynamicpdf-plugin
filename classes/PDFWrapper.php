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
use UnexpectedValueException;

/**
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

    protected ?TemplateRenderer $renderer = null;

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
        $this->pageNumbers = new PageNumbers($text, $position, $size, $font, $margin, $color);

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

        $html = (new LocaleScope($locale))->render(
            [$layout],
            $data,
            fn (array $data): string => $this->parseLayout($layout, $data),
        );

        $this->loadHTML($html, $encoding);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseTemplate(Template $template, array $data = []): string
    {
        return $this->renderer()->template($template, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseLayout(Layout $layout, array $data = []): string
    {
        return $this->renderer()->layout($layout, $data);
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
        return $this->renderer ??= new TemplateRenderer($this, $this->twig());
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
