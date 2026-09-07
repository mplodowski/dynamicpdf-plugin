<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\PDF;
use Cms\Classes\Controller;
use Exception;
use October\Rain\Support\Facades\Twig;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use UnexpectedValueException;

/**
 * @method self setDpi(int $dpi)
 * @method self setIsPhpEnabled(bool $enabled)
 * @method self setIsRemoteEnabled(bool $enabled)
 * @method self setLogOutputFile(string $path)
 */
class PDFWrapper extends PDF
{
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
     */
    public function loadTemplate(string $code, array $data = [], ?string $encoding = null): self
    {
        $template = Template::byCode($code);

        $this->loadHTML(
            $this->parseTemplate($template, $data),
            $encoding,
        );

        if ($template->size) {
            $this->setPaper($template->size, $template->orientation ?? 'portrait');
        }

        $this->allowSelfSignedCertificates();

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

        $this->allowSelfSignedCertificates();

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

        try {
            $twig = (new Controller)->getTwig();
            $template = $twig->createTemplate($markup);

            return $template->render($data);
        } catch (Exception) {
            return Twig::parse($markup, $data);
        }
    }

    /**
     * Remote resources stay limited to the hosts from the dompdf configuration or, when
     * that allows any host, to the application itself, so a template cannot make the server
     * fetch internal addresses.
     */
    public function allowRemoteApplicationAssets(): self
    {
        $options = $this->dompdf->getOptions();

        $hosts = $options->getAllowedRemoteHosts() ?: array_values(array_unique(array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            request()->getHost(),
        ])));

        $options->setIsRemoteEnabled(true)->setAllowedRemoteHosts($hosts);

        return $this;
    }

    protected function allowSelfSignedCertificates(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $this->dompdf->setHttpContext($context);
    }
}
