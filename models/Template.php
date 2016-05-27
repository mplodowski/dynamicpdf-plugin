<?php

namespace Renatio\DynamicPDF\Models;

use Model;
use October\Rain\Database\Traits\Validation;

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
        'code' => 'renatio.dynamicpdf::lang.templates.code'
    ];

}