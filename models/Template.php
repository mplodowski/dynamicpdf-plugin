<?php

namespace Renatio\DynamicPDF\Models;

use ArrayObject;
use Dompdf\Adapter\CPDF;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;
use Throwable;

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

    public const LAYOUT_CACHE = 'renatio.dynamicpdf.layouts';

    public $table = 'renatio_dynamicpdf_pdf_templates';

    /** @var array<string, string> */
    protected $casts = [
        'is_custom' => 'bool',
    ];

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

    /**
     * A stored, non-customised template follows its view file. The row is left as
     * stored when the view is not registered any more or its file is missing.
     */
    public function afterFetch(): void
    {
        if ($this->is_custom || ! $this->code || ! $this->getView()) {
            return;
        }

        try {
            $this->fillFromView($this->code);
        } catch (Throwable $e) {
            Log::error("Renatio.DynamicPDF could not read the view of {$this->code}: {$e->getMessage()}", ['exception' => $e]);
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
        $this->setAttribute('layout', $this->resolveLayout(array_get($sections, 'settings.layout')));
        $this->size = array_get($sections, 'settings.size');
        $this->orientation = array_get($sections, 'settings.orientation');
        $this->description = array_get($sections, 'settings.description');
        $this->content_html = array_get($sections, 'html');
    }

    /**
     * Every view-driven row of a list re-reads its layout, so stored layouts are remembered
     * by code for the current request or queue job (a scoped container instance) and each
     * template gets its own hydrated copy. Unsaved view fallbacks are not remembered.
     */
    protected function resolveLayout(?string $code): ?Layout
    {
        if (! $code) {
            return null;
        }

        $cache = self::layoutCache();

        if (! isset($cache[$code])) {
            try {
                $layout = Layout::byCode($code);
            } catch (ModelNotFoundException) {
                $cache[$code] = false;

                return null;
            }

            if (! $layout->exists) {
                return $layout;
            }

            $cache[$code] = $layout->getAttributes();
        }

        return $cache[$code] === false ? null : Layout::hydrate([$cache[$code]])->first();
    }

    /**
     * Registered in Plugin::register(); bound here as well so a save on a disabled plugin
     * or outside the plugin bootstrap does not fail.
     *
     * @return ArrayObject<string, array<string, mixed>|false>
     */
    public static function layoutCache(): ArrayObject
    {
        if (! app()->bound(self::LAYOUT_CACHE)) {
            app()->scoped(self::LAYOUT_CACHE, fn (): ArrayObject => new ArrayObject);
        }

        return app(self::LAYOUT_CACHE);
    }

    public static function flushLayoutCache(): void
    {
        self::layoutCache()->exchangeArray([]);
    }

    public function getHtmlAttribute(): string
    {
        return PDF::loadTemplate($this->code)->getDompdf()->output_html();
    }

    /**
     * A registered view that is not stored yet (for example before the first backend
     * request synchronised it) renders straight from the file.
     */
    public static function byCode(string $code): self
    {
        $template = static::whereCode($code)->first();

        if ($template instanceof static) {
            return $template;
        }

        if (! array_key_exists($code, PDFManager::instance()->listRegisteredTemplates() ?? [])) {
            throw (new ModelNotFoundException)->setModel(static::class, [$code]);
        }

        $template = new self;
        $template->fillFromView($code);

        return $template;
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
