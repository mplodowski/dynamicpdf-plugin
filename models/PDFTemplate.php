<?php namespace Renatio\DynamicPDF\Models;

use DOMPDF_Exception;
use Redirect;
use Model;
use October\Rain\Database\Traits\Validation;
use Flash;

/**
 * PDFTemplate Model
 */
class PDFTemplate extends Model
{

    /**
     * Traits
     */
    use Validation;

    /**
     * @var PDF Template code
     */
    private static $template_code;

    /**
     * @var Template data
     */
    private static $data;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'renatio_dynamicpdf_pdf_templates';

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'layout' => ['Renatio\DynamicPDF\Models\PDFLayout']
    ];

    /**
     * @var array Validation Rules
     */
    public $rules = [
        'code'  => 'required|unique:renatio_dynamicpdf_pdf_templates',
        'title' => 'required',
    ];

    /**
     * Render PDF Template
     *
     * @param $code
     * @param $data
     * @return mixed
     */
    public static function render($code, $data = [])
    {
        self::initFields($code, $data);

        $html = self::getHtmlForPDF();

        return self::outputPDF($html);
    }

    /**
     * Get HTML from template
     *
     * @return mixed
     */
    private static function getHtmlForPDF()
    {
        $template = self::whereCode(self::getTemplateCode())->firstOrFail();

        $html = self::parsePDFTemplate($template);

        return $html;
    }

    /**
     * Parse PDF template
     *
     * @param PDFTemplate $template
     * @return mixed
     */
    private static function parsePDFTemplate(PDFTemplate $template)
    {
        $twig = \App::make('twig.string');

        $html = $twig->render($template->content_html, self::getData());

        if ($template->layout)
        {
            $html = $twig->render($template->layout->content_html, [
                'content_html'   => $html,
                'background_img' => $template->layout->background_img ? $template->layout->background_img->getPath() : '',
                'css'            => $template->layout->content_css
            ]);
        }

        return $html;
    }

    /**
     * Initialize static fields
     *
     * @param $code
     * @param $data
     */
    private static function initFields($code, $data)
    {
        self::setTemplateCode($code);
        self::setData($data);
    }

    /**
     * @return Template data
     */
    public static function getData()
    {
        return self::$data;
    }

    /**
     * @param $data
     */
    public static function setData($data)
    {
        self::$data = $data;
    }

    /**
     * @return Template code
     */
    public static function getTemplateCode()
    {
        return self::$template_code;
    }

    /**
     * @param $template_code
     */
    public static function setTemplateCode($template_code)
    {
        self::$template_code = $template_code;
    }

    /**
     * Output pdf to browser
     *
     * @param $html
     * @return mixed
     */
    private static function outputPDF($html)
    {
        try
        {
            $pdf = \App::make('dompdf.wrapper');
            $pdf->loadHTML($html);

            return $pdf->stream();

        } catch (DOMPDF_Exception $e)
        {
            Flash::error($e->getMessage());

            return Redirect::back();
        }
    }
}