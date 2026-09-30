<?php

namespace Renatio\DynamicPDF\Models;

use Illuminate\Support\Arr;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ApplicationException;
use October\Rain\Exception\ValidationException;
use Renatio\DynamicPDF\Classes\LessCompiler;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;
use Renatio\DynamicPDF\Traits\DescribesViewStatus;
use Renatio\DynamicPDF\Traits\Duplicates;
use Renatio\DynamicPDF\Traits\FollowsView;
use Renatio\DynamicPDF\Traits\TranslatesContent;
use Renatio\DynamicPDF\Traits\ValidatesCodeFormat;
use Renatio\DynamicPDF\Traits\ValidatesTwigSyntax;
use System\Models\File;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $content_html
 * @property string|null $content_css
 * @property bool $is_locked
 * @property-read File|null $background_img
 * @property-read \October\Rain\Database\Collection<int, Template> $templates
 * @property-read string $html
 */
class Layout extends Model
{
    use DescribesViewStatus;
    use Duplicates;
    use FollowsView;
    use TranslatesContent;
    use ValidatesCodeFormat;
    use ValidatesTwigSyntax;
    use Validation;

    public const VIEW_FIELDS = ['name' => 'name', 'content_html' => 'content_html', 'content_css' => 'content_css'];

    public $table = 'renatio_dynamicpdf_pdf_layouts';

    /** @var array<int, string> */
    public $translatable = ['content_html', 'content_css'];

    /** @var array<string, array<string>> */
    public $rules = [
        'name' => ['required'],
        'code' => ['required', self::CODE_FORMAT, 'unique'],
        'content_html' => ['required'],
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
        return PDF::loadLayout($this->code)->getDompdf()->output_html();
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
    }

    public function isCustomised(): bool
    {
        return $this->exists && ! $this->is_locked;
    }
}
