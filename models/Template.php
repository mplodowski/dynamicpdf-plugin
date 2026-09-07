<?php

namespace Renatio\DynamicPDF\Models;

use Dompdf\Adapter\CPDF;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;

/**
 * @property int $id
 * @property int|null $layout_id
 * @property string $code
 * @property string $title
 * @property string|null $description
 * @property string|null $content_html
 * @property string|null $size
 * @property string|null $orientation
 * @property bool $is_custom
 * @property Layout|null $layout
 * @property-read string $html
 */
class Template extends Model
{
    use Validation;

    public $table = 'renatio_dynamicpdf_pdf_templates';

    /** @var array<string, class-string> */
    public $belongsTo = [
        'layout' => Layout::class,
    ];

    /** @var array<string, array<string>> */
    public $rules = [
        'title' => ['required'],
        'code' => ['required', 'unique:renatio_dynamicpdf_pdf_templates'],
        'content_html' => ['required'],
    ];

    public function afterFetch(): void
    {
        if (! $this->is_custom) {
            $this->fillFromView($this->code);
        }
    }

    public function fillFromCode(): void
    {
        $path = $this->getView();

        if (! $path) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.template.not_found')) . ': ' . $this->code);
        }

        $this->fillFromView($path);
    }

    public function fillFromView(string $path): void
    {
        $sections = PDFParser::sections($path);

        $this->title = array_get($sections, 'settings.title', '???');
        $this->code = $path;
        $this->setAttribute('layout', Layout::whereCode(array_get($sections, 'settings.layout'))->first());
        $this->size = array_get($sections, 'settings.size');
        $this->orientation = array_get($sections, 'settings.orientation');
        $this->description = array_get($sections, 'settings.description');
        $this->content_html = array_get($sections, 'html');
    }

    public function getHtmlAttribute(): string
    {
        return PDF::loadTemplate($this->code)->getDompdf()->output_html();
    }

    public static function byCode(string $code): self
    {
        return static::whereCode($code)->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    public static function getSizeOptions(): array
    {
        $sizes = array_keys(CPDF::$PAPER_SIZES);

        return array_combine($sizes, $sizes);
    }

    /**
     * @return array<string, string>
     */
    public static function getOrientationOptions(): array
    {
        return [
            'portrait' => 'renatio.dynamicpdf::lang.orientation.portrait',
            'landscape' => 'renatio.dynamicpdf::lang.orientation.landscape',
        ];
    }

    public function getView(): ?string
    {
        return array_get(PDFManager::instance()->listRegisteredTemplates(), $this->code);
    }
}
