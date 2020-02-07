<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\PDF as LaravelPDF;
use Cms\Classes\Controller;
use Exception;
use October\Rain\Support\Facades\Twig;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Models\Message;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\PluginManager;

/**
 * Class PDFWrapper
 * @package Renatio\DynamicPDF\Classes
 */
class PDFWrapper extends LaravelPDF
{

    /**
     * Load template HTML
     *
     * @param  string  $code
     * @param  array  $data
     * @param  null  $encoding
     * @return $this
     */
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

    /**
     * Load layout HTML
     *
     * @param  string  $code
     * @param  array  $data
     * @param  null  $encoding
     * @return $this
     */
    public function loadLayout($code, $data = [], $encoding = null)
    {
        $this->loadHTML(
            $this->parseLayout(Layout::byCode($code), $data),
            $encoding
        );

        return $this;
    }

    /**
     * Get parsed HTML from template
     *
     * @param $template
     * @param  array  $data
     * @return mixed
     */
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

    /**
     * Get parsed HTML from layout
     *
     * @param $layout
     * @param  array  $data
     * @return mixed
     */
    public function parseLayout($layout, $data = [])
    {
        $this->setLocale();

        return $this->parseMarkup(
            $layout->content_html,
            $this->layoutData($layout, $data)
        );
    }

    /**
     * @return void
     */
    protected function setLocale()
    {
        if (!PluginManager::instance()->exists('RainLab.Translate')) {
            return;
        }

        Message::$locale = Translator::instance()->getLocale();
    }

    /**
     * @param $layout
     * @param $data
     * @return array
     */
    protected function layoutData($layout, $data)
    {
        return array_merge([
            'background_img' => $layout->background_img ? $layout->background_img->getPath() : null,
            'css' => $layout->getCSS(),
        ], $data);
    }

    /**
     * Parse markup using CMS Twig
     *
     * @param $markup
     * @param $data
     * @return string
     */
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
