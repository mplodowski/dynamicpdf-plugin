<?php

namespace Renatio\DynamicPDF\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Nullable;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ApplicationException;
use October\Rain\Exception\ValidationException;
use Renatio\DynamicPDF\Classes\LessCompiler;
use Renatio\DynamicPDF\Classes\PageNumbers;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;
use Renatio\DynamicPDF\Plugin;
use Renatio\DynamicPDF\Traits\DescribesViewStatus;
use Renatio\DynamicPDF\Traits\Duplicates;
use Renatio\DynamicPDF\Traits\FollowsView;
use Renatio\DynamicPDF\Traits\TranslatesContent;
use Renatio\DynamicPDF\Traits\ValidatesCodeFormat;
use Renatio\DynamicPDF\Traits\ValidatesTwigSyntax;
use System\Models\File;
use System\Models\Parameter;
use Throwable;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $content_html
 * @property string|null $content_css
 * @property bool $is_locked
 * @property string|null $page_numbers
 * @property string|null $page_numbers_text
 * @property float|string|null $page_numbers_size
 * @property string|null $page_numbers_color
 * @property string|null $page_numbers_font
 * @property float|string|null $page_numbers_margin
 * @property-read File|null $background_img
 * @property-read \October\Rain\Database\Collection<int, Template> $templates
 * @property-read string $html
 */
class Layout extends Model
{
    use DescribesViewStatus;
    use Duplicates;
    use FollowsView;
    use Nullable;
    use TranslatesContent;
    use ValidatesCodeFormat;
    use ValidatesTwigSyntax;
    use Validation;

    public const PAGE_NUMBERS_SETTINGS = [
        'page_numbers' => 'pageNumbers',
        'page_numbers_text' => 'pageNumbersText',
        'page_numbers_size' => 'pageNumbersSize',
        'page_numbers_color' => 'pageNumbersColor',
        'page_numbers_font' => 'pageNumbersFont',
        'page_numbers_margin' => 'pageNumbersMargin',
    ];

    public const VIEW_FIELDS = [
        'name' => 'name',
        'content_html' => 'content_html',
        'content_css' => 'content_css',
        'page_numbers' => 'page_numbers',
        'page_numbers_text' => 'page_numbers_text',
        'page_numbers_size' => 'page_numbers_size',
        'page_numbers_color' => 'page_numbers_color',
        'page_numbers_font' => 'page_numbers_font',
        'page_numbers_margin' => 'page_numbers_margin',
    ];

    public $table = 'renatio_dynamicpdf_pdf_layouts';

    /** @var array<int, string> */
    public $translatable = ['content_html', 'content_css', 'page_numbers_text'];

    /** @var array<int, string> */
    protected $nullable = ['page_numbers', 'page_numbers_text', 'page_numbers_size', 'page_numbers_color', 'page_numbers_font', 'page_numbers_margin'];

    /** @var array<string, array<string>> */
    public $rules = [
        'name' => ['required', 'max:255'],
        'code' => ['required', 'max:255', self::CODE_FORMAT, 'unique'],
        'content_html' => ['required'],
        'page_numbers' => ['nullable', 'in:top-left,top-center,top-right,bottom-left,bottom-center,bottom-right'],
        'page_numbers_text' => ['nullable', 'max:255'],
        'page_numbers_size' => ['nullable', 'numeric', 'between:1,72'],
        'page_numbers_color' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
        'page_numbers_font' => ['nullable', 'max:255'],
        'page_numbers_margin' => ['nullable', 'numeric', 'between:0,200'],
    ];

    /** @var array<string, string> */
    public $customMessages = [
        'code.regex' => 'renatio.dynamicpdf::lang.templates.code_format',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_locked' => 'bool',
    ];

    /** @var array<string, array<int|string, mixed>> */
    public $hasMany = [
        'templates' => [Template::class, 'replicate' => false],
    ];

    /** @var array<string, array<int|string, mixed>> */
    public $attachOne = [
        'background_img' => [File::class, 'delete' => true],
    ];

    public function labelAttribute(): string
    {
        return 'name';
    }

    protected function prepareDuplicate(self $copy): void
    {
        $copy->is_locked = false;
    }

    /**
     * @param  object  $fields
     */
    public function filterFields($fields, ?string $context = null): void
    {
        $this->describeViewStatus($fields);
    }

    public function beforeValidate(): void
    {
        $this->exemptStoredCodeFromFormat();

        $errors = $this->twigSyntaxErrors();

        if ($this->content_css && $this->isDirty('content_css')) {
            try {
                (new LessCompiler)->compile($this->content_css);
            } catch (ApplicationException $e) {
                $errors['content_css'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function afterSave(): void
    {
        Template::flushLayoutCache();
    }

    public function beforeDelete(): void
    {
        $titles = Template::where('layout_id', $this->id)->pluck('title');

        if ($titles->isNotEmpty()) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.layout.delete_in_use', ['templates' => $titles->implode(', ')])));
        }
    }

    public function afterDelete(): void
    {
        Template::flushLayoutCache();
    }

    public function getHtmlAttribute(): string
    {
        return PDF::loadLayout($this->code)->getDomPDF()->outputHtml();
    }

    /**
     * @return array<string, string>
     */
    protected static function registeredViews(): array
    {
        return PDFManager::instance()->listRegisteredLayouts();
    }

    public function getCSS(): string
    {
        if (! $this->content_css) {
            return '';
        }

        return (new LessCompiler)->compile($this->content_css);
    }

    public function afterFetch(): void
    {
        if (! $this->is_locked || ! $this->code || ! self::followsViewUpdates() || ! $this->getView()) {
            return;
        }

        $this->inDefaultLocale(function (): void {
            $stored = $this->getAttributes();

            try {
                $this->fillFromView($this->code);
            } catch (Throwable $e) {
                $this->setRawAttributes($stored, true);

                Log::error("Renatio.DynamicPDF could not read the view of {$this->code}: {$e->getMessage()}", ['exception' => $e]);
            }
        });
    }

    public static function followsViewUpdates(): bool
    {
        return (bool) Parameter::get(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER);
    }

    protected function markAsFollowingView(): void
    {
        $this->is_locked = true;
    }

    public function fillFromView(string $code): void
    {
        $sections = (new PDFParser)->parseView($code);

        $this->code = $code;
        $this->name = Arr::get($sections, 'settings.name') ?: $code;
        $this->content_css = Arr::get($sections, 'css');
        $this->content_html = Arr::get($sections, 'html');

        foreach (self::PAGE_NUMBERS_SETTINGS as $attribute => $setting) {
            $value = Arr::get($sections, "settings.{$setting}");
            $this->setAttribute($attribute, $value === '' ? null : $value);
        }
    }

    public function pageNumbers(?string $locale = null): ?PageNumbers
    {
        if (! $this->page_numbers) {
            return null;
        }

        return new PageNumbers(
            $this->page_numbers_text ?: trans('renatio.dynamicpdf::lang.page_numbers.default_text', [], $locale),
            $this->page_numbers,
            is_numeric($this->page_numbers_size) ? (float) $this->page_numbers_size : 9,
            $this->page_numbers_font ?: null,
            is_numeric($this->page_numbers_margin) ? (float) $this->page_numbers_margin : 20,
            $this->pageNumbersColor(),
        );
    }

    /**
     * @return array<int, float>
     */
    protected function pageNumbersColor(): array
    {
        if (! preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', (string) $this->page_numbers_color, $hex)) {
            return [0, 0, 0];
        }

        return array_map(fn (string $component): float => hexdec($component) / 255, array_slice($hex, 1));
    }

    public function isCustomised(): bool
    {
        return $this->exists && ! $this->is_locked;
    }
}
