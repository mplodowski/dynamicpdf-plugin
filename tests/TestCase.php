<?php

namespace Renatio\DynamicPDF\Tests;

use Backend\Facades\Backend;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

abstract class TestCase extends OctoberPestTestCase
{
    /**
     * Posts the update form of a template the way the backend does, through the onSave handler.
     *
     * @param  array<string, mixed>  $fields
     * @return TestResponse<\Illuminate\Http\Response>
     */
    public function saveTemplateForm(int $id, array $fields): TestResponse
    {
        return $this->post(Backend::url('renatio/dynamicpdf/templates/update/' . $id), ['Template' => $fields], [
            'X-AJAX-HANDLER' => 'onSave',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createLayout(array $attributes = []): Layout
    {
        return Layout::create(array_merge([
            'name' => 'Test Layout',
            'code' => 'test.layout.' . uniqid(),
            'content_html' => '<html><body>{{ content_html }}</body></html>',
            'content_css' => '',
        ], $attributes));
    }

    /**
     * Registers PDF views written to a temporary directory under the given namespace and
     * returns that directory; the caller deletes it and forgets the PDFManager instance.
     *
     * @param  array<string, string>  $files  view name without extension => file content
     */
    public function registerViewTemplates(string $namespace, array $files): string
    {
        $directory = sys_get_temp_dir() . '/dynamicpdf-views-' . uniqid();
        File::makeDirectory($directory . '/pdf', 0755, true);
        View::addNamespace($namespace, $directory);

        foreach ($files as $name => $content) {
            File::put("{$directory}/pdf/{$name}.htm", $content);
        }

        PDFManager::instance()->registerTemplates(array_map(fn (string $name): string => "{$namespace}::pdf.{$name}", array_keys($files)));

        return $directory;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createTemplate(array $attributes = []): Template
    {
        return Template::create(array_merge([
            'title' => 'Test Template',
            'code' => 'test.template.' . uniqid(),
            'content_html' => '<p>Test content</p>',
            'is_custom' => true,
        ], $attributes));
    }
}
