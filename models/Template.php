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
        'layout' => 'Renatio\DynamicPDF\Models\Layout'
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

}