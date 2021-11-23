<?php

namespace Renatio\DynamicPDF\Models;

use Dompdf\Adapter\CPDF;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;

class Template extends Model
{
    use Validation;

    public $table = 'renatio_dynamicpdf_pdf_templates';

    public $belongsTo = [
        'layout' => Layout::class,
    ];

    public $rules = [
        'title' => ['required'],
        'code' => ['required', 'unique:renatio_dynamicpdf_pdf_templates'],
        'content_html' => ['required'],
    ];

    public function afterFetch()
    {
        if (! $this->is_custom) {
            $this->fillFromView($this->code);
        }
    }

    public function fillFromCode()
    {
        if (! ($path = $this->getView())) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.template.not_found')).': '.$this->code);
        }

        $this->fillFromView($path);
    }

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

    public function getHtmlAttribute()
    {
        return PDF::loadTemplate($this->code)->getDompdf()->output_html();
    }

    public static function byCode($code)
    {
        return static::whereCode($code)->firstOrFail();
    }

    public static function getSizeOptions()
    {
        $sizes = array_keys(CPDF::$PAPER_SIZES);

        return array_combine($sizes, $sizes);
    }

    public static function getOrientationOptions()
    {
        return [
            'portrait' => 'renatio.dynamicpdf::lang.orientation.portrait',
            'landscape' => 'renatio.dynamicpdf::lang.orientation.landscape',
        ];
    }

    public function getView()
    {
        return array_get(PDFManager::instance()->listRegisteredTemplates(), $this->code);
    }
}
