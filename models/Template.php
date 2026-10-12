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
use Renatio\DynamicPDF\Traits\Duplicates;
use Renatio\DynamicPDF\Traits\FollowsView;
use Renatio\DynamicPDF\Traits\HasRevisions;
use Renatio\DynamicPDF\Traits\ValidatesContent;

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
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property Layout|null $layout
 * @property-read \October\Rain\Database\Collection<int, Revision> $revisions
 * @property-read string $html
 *
 * @method \October\Rain\Database\Relations\MorphMany translations()
 */
class Template extends Model
{
    use Duplicates;
    use FollowsView;
    use HasRevisions;
    use Nullable;
    use ValidatesContent;
    use Validation;

    public const LAYOUT_CACHE = 'renatio.dynamicpdf.layouts';

    public const VIEW_FIELDS = ['title' => 'title', 'description' => 'description', 'content_html' => 'content_html', 'layout' => 'layout_id', 'size' => 'size', 'orientation' => 'orientation'];

    public const REVISION_FIELDS = ['title', 'description', 'content_html', 'layout_id', 'size', 'orientation', 'sample_data'];

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

    /** @var array<string, array<int|string, mixed>> */
    public $morphMany = [
        'revisions' => [Revision::class, 'name' => 'revisionable', 'delete' => true, 'replicate' => false],
    ];

    /** @var array<string, array<string>> */
    public $rules = [
        'title' => ['required', 'max:255'],
        'code' => ['required', 'max:255', self::CODE_FORMAT, 'unique'],
        'content_html' => ['required'],
        'sample_data' => ['nullable', 'json'],
    ];

    /** @var array<string, string> */
    public $attributeNames = [
        'content_html' => 'renatio.dynamicpdf::lang.templates.content_html',
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

    /**
     * @return array<string, mixed>
     */
    protected function restorableRevisionData(Revision $revision): array
    {
        $data = $this->revisionFieldsOf($revision);

        if (isset($data['layout_id']) && ! Layout::whereKey($data['layout_id'])->exists()) {
            $data['layout_id'] = null;
        }

        return $data;
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

        return array_combine($sizes, array_map(self::sizeLabel(...), $sizes));
    }

    protected static function sizeLabel(string $size): string
    {
        return preg_match('/^(\d?[abc]|s?ra)\d+$/', $size) ? strtoupper($size) : ucfirst($size);
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
     * @return array{string|array<int, float>, string}
     */
    public function paper(?Options $options = null): array
    {
        [$size, $orientation] = self::defaultPaper($options);

        return [$this->size ?: $size, $this->orientation ?: $orientation];
    }

    /**
     * @return array{string|array<int, float>, string}
     */
    public static function defaultPaper(?Options $options = null): array
    {
        $options ??= new Options(app('dompdf.options'));

        return [$options->getDefaultPaperSize(), $options->getDefaultPaperOrientation()];
    }

    /**
     * @param  object  $fields
     */
    public function filterFields($fields, ?string $context = null): void
    {
        $this->describeViewStatus($fields);

        [$size, $orientation] = self::defaultPaper();
        $orientation = Arr::get(self::getOrientationOptions(), $orientation);

        if (isset($fields->size) && is_string($size)) {
            $fields->size->emptyOption(trans('renatio.dynamicpdf::lang.options.default', ['value' => self::sizeLabel($size)]));
        }

        if (isset($fields->orientation) && $orientation) {
            $fields->orientation->emptyOption(trans('renatio.dynamicpdf::lang.options.default', ['value' => trans($orientation)]));
        }
    }
}
