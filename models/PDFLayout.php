<?php namespace Renatio\DynamicPDF\Models;

use Model;
use October\Rain\Database\Traits\Validation;

/**
 * PDFLayout Model
 */
class PDFLayout extends Model
{

    /**
     * Traits
     */
    use Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'renatio_dynamicpdf_pdf_layouts';

    /**
     * @var array Validation Rules
     */
    public $rules = [
        'code' => 'required|unique:renatio_dynamicpdf_pdf_layouts',
        'name' => 'required',
    ];

    /**
     * @var array
     */
    public $attachOne = [
        'background_img' => ['System\Models\File']
    ];

}