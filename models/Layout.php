<?php

namespace Renatio\DynamicPDF\Models;

use Model;
use October\Rain\Database\Traits\Validation;

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
    ];

    /**
     * @var array
     */
    protected $fillable = ['name', 'code', 'content_html', 'content_css'];

    /**
     * @var array
     */
    public $attachOne = [
        'background_img' => 'System\Models\File'
    ];

    /**
     * @var array
     */
    public $attributeNames = [
        'name' => 'renatio.dynamicpdf::lang.templates.name',
        'code' => 'renatio.dynamicpdf::lang.templates.code'
    ];

}