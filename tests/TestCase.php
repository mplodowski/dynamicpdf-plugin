<?php

namespace Renatio\DynamicPDF\Tests;

use Backend\Facades\Backend;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\SiteManager;
use System\Models\SiteDefinition;

abstract class TestCase extends OctoberPestTestCase
{
    /**
     * @param  array<string, mixed>  $fields
     * @return TestResponse<\Illuminate\Http\Response>
     */
    public function saveTemplateForm(?int $id, array $fields): TestResponse
    {
        $path = $id === null ? 'renatio/dynamicpdf/templates/create' : 'renatio/dynamicpdf/templates/update/' . $id;

        return $this->post(Backend::url($path), ['Template' => $fields], [
            'X-AJAX-HANDLER' => 'onSave',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TestResponse<\Illuminate\Http\Response>
     */
    public function listAction(string $handler, array $data): TestResponse
    {
        return $this->post(Backend::url('renatio/dynamicpdf/templates'), $data, [
            'X-AJAX-HANDLER' => $handler,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    /**
     * The install already carries the primary site the default locale comes from.
     */
    public function enableTranslation(string $locale = 'de'): SiteDefinition
    {
        config(['multisite.features.renatio_dynamicpdf_template' => true]);

        return $this->createSite($locale);
    }

    public function createSite(string $locale = 'de'): SiteDefinition
    {
        $site = SiteDefinition::create([
            'name' => 'Site ' . $locale,
            'code' => 'site-' . $locale,
            'locale' => $locale,
            'is_enabled' => true,
            'is_prefixed' => true,
            'route_prefix' => '/' . $locale,
        ]);

        SiteManager::instance()->resetCache();

        return $site;
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
     * The caller deletes the returned directory and forgets the PDFManager instance.
     *
     * @param  array<string, string>  $files  view name without extension => file content
     */
    public function registerViewTemplates(string $namespace, array $files, ?string $directory = null): string
    {
        $directory = $this->writeViewFiles($namespace, $files, $directory);

        PDFManager::instance()->registerTemplates($this->viewNames($namespace, $files));

        return $directory;
    }

    /**
     * @param  array<string, string>  $files  view name without extension => file content
     */
    public function registerViewLayouts(string $namespace, array $files, ?string $directory = null): string
    {
        $directory = $this->writeViewFiles($namespace, $files, $directory);

        PDFManager::instance()->registerLayouts($this->viewNames($namespace, $files));

        return $directory;
    }

    /**
     * Writes view files without registering them, for the localized siblings of a registered view.
     *
     * @param  array<string, string>  $files  view name without extension => file content
     */
    public function writeViewFiles(string $namespace, array $files, ?string $directory = null): string
    {
        if ($directory === null) {
            $directory = sys_get_temp_dir() . '/dynamicpdf-views-' . uniqid();
            View::addNamespace($namespace, $directory);
        }

        foreach ($files as $name => $content) {
            $path = "{$directory}/pdf/{$name}.htm";
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $content);
        }

        return $directory;
    }

    /**
     * @param  array<string, string>  $files
     * @return array<int, string>
     */
    protected function viewNames(string $namespace, array $files): array
    {
        return array_map(fn (string $name): string => "{$namespace}::pdf." . str_replace('/', '.', $name), array_keys($files));
    }

    public function findTemplate(string $code): Template
    {
        /** @var Template $template */
        $template = Template::query()->where('code', $code)->firstOrFail();

        return $template;
    }

    public function findLayout(string $code): Layout
    {
        /** @var Layout $layout */
        $layout = Layout::query()->where('code', $code)->firstOrFail();

        return $layout;
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
