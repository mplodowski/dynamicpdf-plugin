<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\PDF;
use Cms\Classes\Controller;
use Exception;
use October\Rain\Support\Facades\Twig;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

class PDFWrapper extends PDF
{
    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        }

        if (method_exists($this->dompdf, $method)) {
            $return = $this->dompdf->$method(...$parameters);

            return $return == $this->dompdf ? $this : $return;
        }

        $options = $this->dompdf->getOptions();

        if (method_exists($options, $method)) {
            call_user_func_array([$options, $method], $parameters);
        }

        return $this;
    }

    public function loadTemplate($code, $data = [], $encoding = null)
    {
        $template = Template::byCode($code);

        $this->loadHTML(
            $this->parseTemplate($template, $data),
            $encoding
        );

        if ($template->size) {
            $this->setPaper($template->size, $template->orientation ?? 'portrait');
        }

        $this->allowSelfSignedCertificates();

        return $this;
    }

    public function loadLayout($code, $data = [], $encoding = null)
    {
        $this->loadHTML(
            $this->parseLayout(Layout::byCode($code), $data),
            $encoding
        );

        $this->allowSelfSignedCertificates();

        return $this;
    }

    public function parseTemplate($template, $data = [])
    {
        $html = $this->parseMarkup($template->content_html, $data);

        if (! $template->layout) {
            return $html;
        }

        return $this->parseLayout(
            $template->layout,
            array_merge(['content_html' => $html], $data)
        );
    }

    public function parseLayout($layout, $data = [])
    {
        return $this->parseMarkup(
            $layout->content_html,
            $this->layoutData($layout, $data)
        );
    }

    protected function layoutData($layout, $data)
    {
        return array_merge([
            'background_img' => $layout->background_img ? $layout->background_img->getPath() : null,
            'css' => $layout->getCSS(),
        ], $data);
    }

    protected function parseMarkup($markup, $data)
    {
        try {
            $twig = (new Controller)->getTwig();
            $template = $twig->createTemplate($markup);

            return $template->render($data);
        } catch (Exception $e) {
            return Twig::parse($markup, $data);
        }
    }

    protected function allowSelfSignedCertificates()
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
