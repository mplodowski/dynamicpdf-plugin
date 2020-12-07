<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\PDF;
use Cms\Classes\Controller;
use Exception;
use October\Rain\Support\Facades\Twig;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Models\Message;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\PluginManager;

class PDFWrapper extends PDF
{

    public function __call($method, $args)
    {
        $options = $this->getDomPDF()->getOptions();

        if (method_exists($options, $method)) {
            call_user_func_array([$options, $method], $args);
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

        return $this;
    }

    public function loadLayout($code, $data = [], $encoding = null)
    {
        $this->loadHTML(
            $this->parseLayout(Layout::byCode($code), $data),
            $encoding
        );

        return $this;
    }

    public function parseTemplate($template, $data = [])
    {
        $this->setLocale();

        $html = $this->parseMarkup($template->content_html, $data);

        if (!$template->layout) {
            return $html;
        }

        return $this->parseLayout(
            $template->layout,
            array_merge(['content_html' => $html], $data)
        );
    }

    public function parseLayout($layout, $data = [])
    {
        $this->setLocale();

        return $this->parseMarkup(
            $layout->content_html,
            $this->layoutData($layout, $data)
        );
    }

    protected function setLocale()
    {
        if (!PluginManager::instance()->exists('RainLab.Translate')) {
            return;
        }

        Message::$locale = Translator::instance()->getLocale();
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
}
