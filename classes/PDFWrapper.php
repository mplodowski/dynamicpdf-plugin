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
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\SiteManager;
use System\Facades\System;
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
     */
    public function loadTemplate(string $code, array $data = [], ?string $encoding = null, ?string $layout = null): self
    {
        $template = Template::byCode($code);

        if ($layout !== null) {
            $template->setRelation('layout', Layout::byCode($layout));
        }

        $this->loadHTML(
            $this->parseTemplate($template, $data),
            $encoding,
        );

        if ($template->size) {
            $this->setPaper($template->size, $template->orientation ?? 'portrait');
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function loadLayout(string $code, array $data = [], ?string $encoding = null): self
    {
        $this->loadHTML(
            $this->parseLayout(Layout::byCode($code), $data),
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
     */
    public function allowRemoteApplicationAssets(): self
    {
        $options = $this->dompdf->getOptions();

        $hosts = array_merge($options->getAllowedRemoteHosts() ?: [], $this->applicationHosts());

        $options->setIsRemoteEnabled(true)->setAllowedRemoteHosts(array_values(array_unique($hosts)));

        return $this;
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
