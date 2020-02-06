<?php

namespace Renatio\DynamicPDF\Models;

use Exception;
use Less_Parser;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use Renatio\DynamicPDF\Classes\PDF;
use System\Models\File;

/**
 * Class Layout
 * @package Renatio\DynamicPDF\Models
 */
class Layout extends Model
{

    use Validation;

    /**
     * @var string
     */
    public $table = 'renatio_dynamicpdf_pdf_layouts';

    /**
     * @var array
     */
    public $rules = [
        'name' => 'required',
        'code' => 'required|unique:renatio_dynamicpdf_pdf_layouts',
        'content_html' => 'required',
    ];

    /**
     * @var array
     */
    public $attachOne = [
        'background_img' => File::class,
    ];

    /**
     * @return mixed
     */
    public function getHtmlAttribute()
    {
        return PDF::loadLayout($this->code)->getDompdf()->output_html();
    }

    /**
     * Find layout by code
     *
     * @param $code
     * @return mixed
     */
    public static function byCode($code)
    {
        return static::whereCode($code)->firstOrFail();
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getCSS()
    {
        $parser = new Less_Parser;

        return $parser->parse($this->content_css)->getCss();
    }
}
