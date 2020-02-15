<?php

namespace Renatio\DynamicPDF\Models;

use Dompdf\Adapter\CPDF;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFParser;

/**
 * Class Template
 * @package Renatio\DynamicPDF\Models
 */
class Template extends Model
{

    use Validation;

    /**
     * @var string
     */
    public $table = 'renatio_dynamicpdf_pdf_templates';

    /**
     * @var array
     */
    public $belongsTo = [
        'layout' => Layout::class,
    ];

    /**
     * @var array
     */
    public $rules = [
        'title' => 'required',
        'code' => 'required|unique:renatio_dynamicpdf_pdf_templates',
        'content_html' => 'required',
    ];

    /**
     * @return void
     * @throws FileNotFoundException
     */
    public function afterFetch()
    {
        if (!$this->is_custom) {
            $this->fillFromView($this->code);
        }
    }

    /**
     * @param $path
     * @throws FileNotFoundException
     */
    public function fillFromView($path)
    {
        $sections = PDFParser::sections($path);

        $this->title = array_get($sections, 'settings.title', '???');
        $this->code = $path;
        $this->layout = Layout::whereCode(array_get($sections, 'settings.layout'))->first();
        $this->size = array_get($sections, 'settings.size');
        $this->orientation = array_get($sections, 'settings.orientation');
        $this->description = array_get($sections, 'settings.description');
        $this->content_html = array_get($sections, 'html');
    }

    /**
     * @return mixed
     */
    public function getHtmlAttribute()
    {
        return PDF::loadTemplate($this->code)->getDompdf()->output_html();
    }

    /**
     * Find template by code
     *
     * @param $code
     * @return mixed
     */
    public static function byCode($code)
    {
        return static::whereCode($code)->firstOrFail();
    }

    /**
     * @return array
     */
    public static function getSizeOptions()
    {
        $sizes = array_keys(CPDF::$PAPER_SIZES);

        return array_combine($sizes, $sizes);
    }

    /**
     * @return array
     */
    public static function getOrientationOptions()
    {
        return [
            'portrait' => 'renatio.dynamicpdf::lang.orientation.portrait',
            'landscape' => 'renatio.dynamicpdf::lang.orientation.landscape',
        ];
    }
}
