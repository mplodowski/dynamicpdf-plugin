<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Database\Eloquent\Collection;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Throwable;

class ExportTemplates
{
    public const FORMAT = 'renatio-dynamicpdf';

    public const VERSION = 1;

    public const LAYOUT_FIELDS = [
        'name',
        'content_html',
        'content_css',
        'is_locked',
        'page_numbers',
        'page_numbers_text',
        'page_numbers_size',
        'page_numbers_color',
        'page_numbers_font',
        'page_numbers_margin',
    ];

    public const TEMPLATE_FIELDS = ['title', 'description', 'content_html', 'size', 'orientation', 'sample_data', 'is_custom'];

    /** @var array<int, string> */
    protected array $missingBackgrounds = [];

    /** @var array<int, string> */
    protected array $layoutCodes = [];

    /**
     * @param  array<int, string>  $codes  template or layout codes, everything when empty
     * @return array<string, mixed>
     */
    public function handle(array $codes = []): array
    {
        $this->missingBackgrounds = [];

        /** @var Collection<int, Template> $templates */
        $templates = Template::with('translations')
            ->when($codes !== [], fn ($query) => $query->whereIn('code', $codes))
            ->orderBy('code')
            ->get();

        /** @var Collection<int, Layout> $layouts */
        $layouts = Layout::with(['translations', 'background_img'])
            ->when($codes !== [], fn ($query) => $query->whereIn('code', $codes)->orWhereIn('id', $templates->pluck('layout_id')->filter()))
            ->orderBy('code')
            ->get();

        $unknown = array_diff($codes, $templates->pluck('code')->all(), $layouts->pluck('code')->all());

        if ($unknown !== []) {
            throw new ApplicationException('No template or layout has the code ' . implode(', ', $unknown) . '.');
        }

        $this->layoutCodes = $layouts->pluck('code', 'id')->all();

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'layouts' => $layouts->map(fn (Layout $layout): array => $this->layout($layout))->all(),
            'templates' => $templates->map(fn (Template $template): array => $this->template($template))->all(),
        ];
    }

    /**
     * @return array<int, string> codes of the layouts exported without their missing background file
     */
    public function missingBackgrounds(): array
    {
        return $this->missingBackgrounds;
    }

    /**
     * @return array<string, mixed>
     */
    protected function layout(Layout $layout): array
    {
        return [
            'code' => $layout->code,
            ...$layout->only(self::LAYOUT_FIELDS),
            'translations' => $this->translations($layout),
            'background' => $this->background($layout),
        ];
    }

    /**
     * @return array<string, string>|null
     */
    protected function background(Layout $layout): ?array
    {
        $background = $layout->background_img;

        if ($background === null) {
            return null;
        }

        try {
            $data = $background->getContents();
        } catch (Throwable) {
            $data = null;
        }

        if (! is_string($data) || $data === '') {
            $this->missingBackgrounds[] = $layout->code;

            return null;
        }

        return ['file_name' => $background->getFilename(), 'data' => base64_encode($data)];
    }

    /**
     * @return array<string, mixed>
     */
    protected function template(Template $template): array
    {
        return [
            'code' => $template->code,
            ...$template->only(self::TEMPLATE_FIELDS),
            'layout' => $this->layoutCodes[$template->layout_id] ?? null,
            'translations' => $this->translations($template),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function translations(Layout|Template $model): array
    {
        $translations = [];

        foreach (array_diff($model->getTranslatedLocales(), [$model->getTranslatableDefault()]) as $locale) {
            foreach ($model->getTranslatableAttributes() as $attribute) {
                $value = $model->getTranslation($attribute, $locale, false);

                if ($value !== null) {
                    $translations[$locale][$attribute] = $value;
                }
            }
        }

        return $translations;
    }
}
