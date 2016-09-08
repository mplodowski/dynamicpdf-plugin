<?php

namespace Renatio\DynamicPDF\Models;

use Model;
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
        'name' => 'required|max:100',
        'code' => 'required|max:50|unique:renatio_dynamicpdf_pdf_layouts',
        'content_html' => 'required',
    ];

    /**
     * @var array
     */
    protected $fillable = ['name', 'code', 'content_html', 'content_css'];

    /**
     * @var array
     */
    public $attachOne = [
        'background_img' => File::class,
    ];

    /**
     * @var array
     */
    public $attributeNames = [
        'name' => 'renatio.dynamicpdf::lang.templates.name',
        'code' => 'renatio.dynamicpdf::lang.templates.code',
        'content_html' => 'renatio.dynamicpdf::lang.templates.content_html'
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

}