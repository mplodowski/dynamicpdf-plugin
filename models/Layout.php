<?php

namespace Renatio\DynamicPDF\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Less_Parser;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;
use Renatio\DynamicPDF\Traits\Duplicates;
use Renatio\DynamicPDF\Traits\TranslatesContent;
use System\Models\File;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $content_html
 * @property string|null $content_css
 * @property bool $is_locked
 * @property-read File|null $background_img
 * @property-read string $html
 */
class Layout extends Model
{
    use Duplicates;
    use TranslatesContent;
    use Validation;

    public $table = 'renatio_dynamicpdf_pdf_layouts';

    /** @var array<int, string> */
    public $translatable = ['content_html', 'content_css'];

    /** @var array<string, array<string>> */
    public $rules = [
        'name' => ['required'],
        'code' => ['required', 'unique:renatio_dynamicpdf_pdf_layouts'],
        'content_html' => ['required'],
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_locked' => 'bool',
    ];

    /** @var array<string, array<int|string, mixed>> */
    public $attachOne = [
        'background_img' => [File::class, 'delete' => true],
    ];

    protected function duplicateLabelAttribute(): string
    {
        return 'name';
    }

    protected function prepareDuplicate(self $copy): void
    {
        $copy->is_locked = false;
    }

    public function afterSave(): void
    {
        Template::flushLayoutCache();
    }

    public function afterDelete(): void
    {
        Template::flushLayoutCache();
    }

    public function getHtmlAttribute(): string
    {
        return PDF::loadLayout($this->code)->getDompdf()->output_html();
    }

    public static function byCode(string $code): self
    {
        $layout = static::whereCode($code)->first();

        if ($layout instanceof static) {
            return $layout;
        }

        if (! array_key_exists($code, PDFManager::instance()->listRegisteredLayouts())) {
            throw (new ModelNotFoundException)->setModel(static::class, [$code]);
        }

        $layout = new self;
        $layout->fillFromView($code);

        return $layout;
    }

    public function getCSS(): string
    {
        if (! $this->content_css) {
            return '';
        }

        return (new Less_Parser)->parse($this->content_css)->getCss();
    }

    /**
     * Restores the stored row from its view file and hands it back to the sync.
     */
    public function resetToView(): void
    {
        $this->inDefaultLocale(function (): void {
            $this->fillFromCode();
            $this->save();
        });
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

        $this->code = $path;
        $this->name = array_get($sections, 'settings.name', '???');
        $this->content_css = array_get($sections, 'css');
        $this->content_html = array_get($sections, 'html');
    }

    public function getView(): ?string
    {
        return array_get(PDFManager::instance()->listRegisteredLayouts(), $this->code);
    }

    /**
     * Layouts carry no is_custom flag: a stored one is view-driven while it stays locked.
     */
    public function isCustomised(): bool
    {
        return $this->exists && ! $this->is_locked;
    }

    /**
     * A record the sync would recreate from its view file; deleting it only makes it come back.
     */
    public function followsView(): bool
    {
        return (bool) $this->getView();
    }
}
