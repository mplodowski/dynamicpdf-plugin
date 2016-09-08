<?php

namespace Renatio\DynamicPDF\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use Renatio\DynamicPDF\Classes\PDF;

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
        'title' => 'required|max:100',
        'code' => 'required|max:50|unique:renatio_dynamicpdf_pdf_templates',
        'content_html' => 'required',
    ];

    /**
     * @var array
     */
    protected $fillable = ['title', 'description', 'code', 'content_html', 'layout'];

    /**
     * @var array
     */
    public $attributeNames = [
        'title' => 'renatio.dynamicpdf::lang.templates.title',
        'code' => 'renatio.dynamicpdf::lang.templates.code',
        'content_html' => 'renatio.dynamicpdf::lang.templates.content_html'
    ];

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

}