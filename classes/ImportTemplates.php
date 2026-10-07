<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ApplicationException;
use October\Rain\Exception\ValidationException;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Models\File;
use Throwable;
use Twig\Error\Error as TwigError;

class ImportTemplates
{
    /** @var array<int, array{string, string, string}> */
    protected array $report = [];

    /** @var array<int, File> */
    protected array $storedFiles = [];

    /** @var array<int, File> */
    protected array $replacedFiles = [];

    /**
     * @param  array<mixed>  $payload
     */
    public function handle(array $payload, bool $force = false): void
    {
        $this->report = $this->storedFiles = $this->replacedFiles = [];

        $this->validate($payload);

        try {
            DB::transaction(function () use ($payload, $force): void {
                foreach ($payload['layouts'] as $record) {
                    $this->import(Layout::class, $record, $force, fn (Layout $layout) => $this->fillLayout($layout, $record));
                }

                foreach ($payload['templates'] as $record) {
                    $this->import(Template::class, $record, $force, fn (Template $template) => $this->fillTemplate($template, $record));
                }
            });
        } catch (Throwable $e) {
            $this->deleteUnusedFiles($this->storedFiles);

            throw $e;
        }

        $this->deleteUnusedFiles($this->replacedFiles);
    }

    /**
     * @return array<int, array{string, string, string}> type, code and outcome of each record
     */
    public function report(): array
    {
        return $this->report;
    }

    /**
     * @param  array<mixed>  $payload
     */
    protected function validate(array $payload): void
    {
        if (($payload['format'] ?? null) !== ExportTemplates::FORMAT) {
            throw new ApplicationException('The file is not a Renatio.DynamicPDF export.');
        }

        if (($payload['version'] ?? null) !== ExportTemplates::VERSION) {
            throw new ApplicationException('The export format version ' . json_encode($payload['version'] ?? null) . ' is not supported, expected ' . ExportTemplates::VERSION . '.');
        }

        $rules = [
            'layouts' => 'present|array',
            'layouts.*' => 'array',
            'layouts.*.code' => 'required|string',
            'layouts.*.background' => 'nullable|array',
            'layouts.*.background.file_name' => 'required_with:layouts.*.background|string',
            'layouts.*.background.data' => 'required_with:layouts.*.background|string',
            'templates' => 'present|array',
            'templates.*' => 'array',
            'templates.*.code' => 'required|string',
            'templates.*.layout' => 'nullable|string',
        ];

        foreach (['layouts' => ExportTemplates::LAYOUT_FIELDS, 'templates' => ExportTemplates::TEMPLATE_FIELDS] as $group => $fields) {
            foreach ($fields as $field) {
                $rules["{$group}.*.{$field}"] = match (true) {
                    str_starts_with($field, 'is_') => 'boolean',
                    in_array($field, ['page_numbers_size', 'page_numbers_margin'], true) => 'nullable|numeric',
                    default => 'nullable|string',
                };
            }

            $rules["{$group}.*.translations"] = 'array';
            $rules["{$group}.*.translations.*"] = 'array';
            $rules["{$group}.*.translations.*.*"] = 'nullable|string';
        }

        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            throw new ApplicationException('The file has an invalid structure. ' . implode(' ', $validator->errors()->all()));
        }
    }

    /**
     * @param  class-string<Layout|Template>  $class
     * @param  array<string, mixed>  $record
     */
    protected function import(string $class, array $record, bool $force, callable $fill): void
    {
        $type = strtolower(class_basename($class));
        $code = $record['code'];

        /** @var Layout|Template|null $model */
        $model = $class::query()->where('code', $code)->first();

        if ($model !== null && ! $force) {
            $this->report[] = [$type, $code, 'skipped'];

            return;
        }

        $outcome = $model === null ? 'created' : 'updated';
        $model ??= new $class;
        $model->code = $code;

        try {
            $fill($model);
            $this->translate($model, $record['translations'] ?? []);
            $model->save();
        } catch (ValidationException $e) {
            throw new ApplicationException("The {$type} {$code} is invalid. " . implode(' ', $e->getErrors()->all()));
        } catch (ApplicationException|TwigError $e) {
            throw new ApplicationException("The {$type} {$code} is invalid. {$e->getMessage()}");
        }

        $this->report[] = [$type, $code, $outcome];
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function fillLayout(Layout $layout, array $record): void
    {
        $layout->forceFill(Arr::only($record, ExportTemplates::LAYOUT_FIELDS));

        if ($layout->background_img !== null) {
            $this->replacedFiles[] = $layout->background_img;
        }

        $layout->setAttribute('background_img', $this->background($record['background'] ?? null));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function fillTemplate(Template $template, array $record): void
    {
        $template->forceFill(Arr::only($record, ExportTemplates::TEMPLATE_FIELDS));

        $layoutCode = $record['layout'] ?? null;
        $layout = $layoutCode === null ? null : Layout::query()->where('code', $layoutCode)->first();

        if ($layoutCode !== null && $layout === null) {
            throw new ApplicationException("The layout {$layoutCode} does not exist and is not in the file.");
        }

        $template->setAttribute('layout', $layout);
    }

    /**
     * @param  array<string, string>|null  $background
     */
    protected function background(?array $background): ?File
    {
        if ($background === null) {
            return null;
        }

        $data = base64_decode($background['data'], true);

        if ($data === false) {
            throw new ApplicationException('The background image is not valid base64.');
        }

        $file = (new File)->fromData($data, $background['file_name']);
        $this->storedFiles[] = $file;

        if (! str_starts_with((string) $file->getContentType(), 'image/')) {
            throw new ApplicationException('The background is not an image.');
        }

        return $file;
    }

    /**
     * Translations bypass the model validation, so their markup is checked here.
     *
     * @param  array<string, array<string, string|null>>  $translations
     */
    protected function translate(Layout|Template $model, array $translations): void
    {
        $attributes = $model->getTranslatableAttributes();

        if ($model->exists) {
            foreach ($attributes as $attribute) {
                $model->forgetTranslations($attribute);
            }
        }

        foreach (Arr::except($translations, $model->getTranslatableDefault()) as $locale => $values) {
            foreach (Arr::only($values, $attributes) as $attribute => $value) {
                if ($value !== null && $value !== '') {
                    match ($attribute) {
                        'content_html' => (new TwigRenderer)->checkSyntax($value, "{$model->code} ({$locale})"),
                        'content_css' => (new LessCompiler)->compile($value),
                        default => null,
                    };
                }

                $model->setTranslation($attribute, $locale, $value);
            }
        }
    }

    /**
     * The attachment rows are already gone or rolled back; this removes the stored file
     * only when no row references it.
     *
     * @param  array<int, File>  $files
     */
    protected function deleteUnusedFiles(array $files): void
    {
        foreach ($files as $file) {
            $file->afterDelete();
        }
    }
}
