<?php

namespace Renatio\DynamicPDF\Models;

use Less_Parser;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;
use System\Models\File;

class Layout extends Model
{
    use Validation;

    public $table = 'renatio_dynamicpdf_pdf_layouts';

    /** @var array<string, array<string>> */
    public $rules = [
        'name' => ['required'],
        'code' => ['required', 'unique:renatio_dynamicpdf_pdf_layouts'],
        'content_html' => ['required'],
    ];

    /** @var array<string, class-string> */
    public $attachOne = [
        'background_img' => File::class,
    ];

    public function getHtmlAttribute(): string
    {
        return PDF::loadLayout($this->code)->getDompdf()->output_html();
    }

    public static function byCode(string $code): self
    {
        return static::whereCode($code)->firstOrFail();
    }

    public function getCSS(): string
    {
        $parser = new Less_Parser;

        return $parser->parse($this->content_css)->getCss();
    }

    public function fillFromCode(): void
    {
        $path = $this->getView();

        if (! $path) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.layout.not_found')) . ': ' . $this->code);
        }

        $this->fillFromView($path);
    }

    public function fillFromView(string $path): void
    {
        $sections = PDFParser::sections($path);

        $this->name = array_get($sections, 'settings.name', '???');
        $this->content_css = array_get($sections, 'css');
        $this->content_html = array_get($sections, 'html');
    }

    public function getView(): ?string
    {
        return array_get(PDFManager::instance()->listRegisteredLayouts(), $this->code);
    }
}
