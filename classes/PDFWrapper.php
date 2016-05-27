<?php

namespace Renatio\DynamicPDF\Classes;

use App;
use Barryvdh\DomPDF\PDF as LaravelPDF;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Twig;

/**
 * Class PDFWrapper
 * @package Renatio\DynamicPDF\Classes
 */
class PDFWrapper extends LaravelPDF
{

    public function __construct()
    {
        $pdf = App::make('dompdf.wrapper');

        parent::__construct($pdf->dompdf, $pdf->config, $pdf->files, $pdf->view);
    }

    /**
     * Load template HTML
     *
     * @param string $code
     * @param array $data
     * @param null $encoding
     * @return $this
     */
    public function loadTemplate($code, array $data = array(), $encoding = null)
    {
        $template = Template::whereCode($code)->firstOrFail();

        $html = $this->parseTemplate($template, $data);

        if ($template->layout) {
            $html = $this->parseLayout($template->layout, ['content_html' => $html]);
        }

        $this->loadHTML($html, $encoding);

        return $this;
    }

    /**
     * Load layout HTML
     *
     * @param string $code
     * @param array $data
     * @param null $encoding
     * @return $this
     */
    public function loadLayout($code, array $data = array(), $encoding = null)
    {
        $layout = Layout::whereCode($code)->firstOrFail();

        $html = $this->parseLayout($layout, $data);

        $this->loadHTML($html, $encoding);

        return $this;
    }

    /**
     * Get parsed HTML from template
     *
     * @param Template $template
     * @param array $data
     * @return mixed
     */
    public function parseTemplate(Template $template, array $data = [])
    {
        return Twig::parse($template->content_html, $data);
    }

    /**
     * Get parsed HTML from layout
     *
     * @param Layout $layout
     * @param array $mergeData
     * @return mixed
     */
    public function parseLayout(Layout $layout, array $mergeData = array())
    {
        $data = [
            'background_img' => $layout->background_img ? $layout->background_img->getPath() : '',
            'css' => $layout->content_css
        ];

        return Twig::parse($layout->content_html, array_merge($data, $mergeData));
    }

}