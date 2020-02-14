<?php

namespace Renatio\DynamicPDF\Models;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Less_Parser;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;
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

    /**
     * @param  null  $code
     * @throws ApplicationException
     * @throws FileNotFoundException
     */
    public function fillFromCode($code = null)
    {
        $registeredLayouts = PDFManager::instance()->listRegisteredLayouts();

        if ($code === null) {
            $code = $this->code;
        }

        if (!$path = array_get($registeredLayouts, $code)) {
            throw new ApplicationException('Unable to find a registered layout with code: '.$code);
        }

        $this->fillFromView($path);
    }

    /**
     * @param $path
     * @throws FileNotFoundException
     */
    public function fillFromView($path)
    {
        $sections = PDFParser::sections($path);

        $this->name = array_get($sections, 'settings.name', '???');
        $this->content_css = array_get($sections, 'css');
        $this->content_html = array_get($sections, 'html');
    }
}
