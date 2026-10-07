<?php

namespace Renatio\DynamicPDF\Models;

use ArrayObject;
use Dompdf\Adapter\CPDF;
use Dompdf\Options;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Nullable;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ValidationException;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;
use Renatio\DynamicPDF\Traits\DescribesViewStatus;
use Renatio\DynamicPDF\Traits\Duplicates;
use Renatio\DynamicPDF\Traits\FollowsView;
use Renatio\DynamicPDF\Traits\TranslatesContent;
use Renatio\DynamicPDF\Traits\ValidatesCodeFormat;
use Renatio\DynamicPDF\Traits\ValidatesTwigSyntax;
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
 * @property string|null $sample_data
 * @property Layout|null $layout
 * @property-read string $html
 *
 * @method \October\Rain\Database\Relations\MorphMany translations()
 */
class Template extends Model
{
    use DescribesViewStatus;
    use Duplicates;
    use FollowsView;
    use Nullable;
    use TranslatesContent;
    use ValidatesCodeFormat;
    use ValidatesTwigSyntax;
    use Validation;

    public const LAYOUT_CACHE = 'renatio.dynamicpdf.layouts';

    public const VIEW_FIELDS = ['title' => 'title', 'description' => 'description', 'content_html' => 'content_html', 'layout' => 'layout_id', 'size' => 'size', 'orientation' => 'orientation'];

    public $table = 'renatio_dynamicpdf_pdf_templates';

    /** @var array<int, string> */
    public $translatable = ['title', 'content_html'];

    /** @var array<int, string> */
    protected $nullable = ['description', 'size', 'orientation', 'sample_data'];

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
        'title' => ['required', 'max:255'],
        'code' => ['required', 'max:255', self::CODE_FORMAT, 'unique'],
        'content_html' => ['required'],
        'sample_data' => ['nullable', 'json'],
    ];

    /** @var array<string, string> */
    public $customMessages = [
        'code.regex' => 'renatio.dynamicpdf::lang.templates.code_format',
    ];

    /**
     * @return array<string, mixed>
     */
    public function sampleData(): array
    {
        $data = json_decode((string) $this->sample_data, true);

        return is_array($data) ? $data : [];
    }

    public function beforeValidate(): void
    {
        $this->exemptStoredCodeFromFormat();

        $errors = $this->twigSyntaxErrors();

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function labelAttribute(): string
    {
        return 'title';
    }

    protected function prepareDuplicate(self $copy): void
    {
        $copy->is_custom = true;
    }

    public function afterFetch(): void
    {
        if ($this->is_custom || ! $this->code || ! $this->getView()) {
            return;
        }

        $this->inDefaultLocale(function (): void {
            $stored = $this->getAttributes();

            try {
                $this->fillFromView($this->code);
                $this->viewReadFailed = false;
            } catch (Throwable $e) {
                $this->setRawAttributes($stored, true);
                $this->unsetRelation('layout');

                $this->failedToReadView($e);
            }
        });
    }

    protected function markAsFollowingView(): void
    {
        $this->is_custom = false;
    }

    public function fillFromView(string $code): void
    {
        $sections = (new PDFParser)->parseView($code);

        $this->title = Arr::get($sections, 'settings.title') ?: $code;
        $this->code = $code;
        $this->setAttribute('layout', $this->resolveLayout(Arr::get($sections, 'settings.layout')));
        $this->size = self::lowercaseOption(Arr::get($sections, 'settings.size'));
        $this->orientation = self::lowercaseOption(Arr::get($sections, 'settings.orientation'));
        $description = Arr::get($sections, 'settings.description');
        $this->description = $description === '' ? null : $description;
        $this->content_html = Arr::get($sections, 'html');
    }

    protected static function lowercaseOption(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : mb_strtolower((string) $value);
    }

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
     * A disabled plugin registers no binding, so its models run without the cache.
     *
     * @return ArrayObject<string, array<string, mixed>|false>
     */
    public static function layoutCache(): ArrayObject
    {
        return app()->bound(self::LAYOUT_CACHE) ? app(self::LAYOUT_CACHE) : new ArrayObject;
    }

    public static function flushLayoutCache(): void
    {
        self::layoutCache()->exchangeArray([]);
    }

    public function getHtmlAttribute(): string
    {
        return PDF::forBrowser()->loadTemplateModel($this, $this->sampleData())->getDomPDF()->outputHtml();
    }

    /**
     * @return array<string, string>
     */
    protected static function registeredViews(): array
    {
        return PDFManager::instance()->listRegisteredTemplates();
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

    /**
     * @param  object  $fields
     */
    public function filterFields($fields, ?string $context = null): void
    {
        $this->describeViewStatus($fields);

        $options = new Options(app('dompdf.options'));
        $size = $options->getDefaultPaperSize();
        $orientation = Arr::get(self::getOrientationOptions(), $options->getDefaultPaperOrientation());

        if (isset($fields->size) && is_string($size)) {
            $fields->size->emptyOption(trans('renatio.dynamicpdf::lang.options.default', ['value' => ucfirst($size)]));
        }

        if (isset($fields->orientation) && $orientation) {
            $fields->orientation->emptyOption(trans('renatio.dynamicpdf::lang.options.default', ['value' => trans($orientation)]));
        }
    }

    public function isCustomised(): bool
    {
        return (bool) $this->is_custom;
    }
}
