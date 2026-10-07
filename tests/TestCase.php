<?php

namespace Renatio\DynamicPDF\Tests;

use Backend\Facades\Backend;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use LogicException;
use Monolog\Handler\NullHandler;
use October\Rain\Foundation\Application;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\SiteManager;
use System\Models\SiteDefinition;

abstract class TestCase extends OctoberPestTestCase
{
    protected static ?string $fontDirectory = null;

    /** @var array<int, string> */
    protected array $temporaryDirectories = [];

    public function setUpOctoberPlugin(): void
    {
        config([
            'logging.channels.dynamicpdf-tests' => ['driver' => 'monolog', 'handler' => NullHandler::class],
            'logging.default' => 'dynamicpdf-tests',
            'app.debug' => false,
            'dompdf.options.font_dir' => self::fontDirectory(),
            'dompdf.options.font_cache' => self::fontDirectory(),
        ]);

        parent::setUpOctoberPlugin();
    }

    public function tearDownOctoberPlugin(): void
    {
        try {
            parent::tearDownOctoberPlugin();
        } finally {
            foreach ($this->temporaryDirectories as $directory) {
                File::deleteDirectory($directory);
            }

            $this->temporaryDirectories = [];
        }
    }

    public function temporaryDirectory(string $prefix): string
    {
        $directory = sys_get_temp_dir() . "/dynamicpdf-{$prefix}-" . Str::uuid();
        File::ensureDirectoryExists($directory);

        return $this->temporaryDirectories[] = $directory;
    }

    public function useThemesPath(string $path): void
    {
        $app = app();

        if (! $app instanceof Application) {
            throw new LogicException('Switching the themes path needs the October application.');
        }

        $app->useThemesPath($path);
    }

    /**
     * One per process: dompdf fills it with font metrics that are slow to rebuild for every test.
     */
    protected static function fontDirectory(): string
    {
        if (self::$fontDirectory === null) {
            $directory = sys_get_temp_dir() . '/dynamicpdf-fonts-' . Str::uuid();
            File::ensureDirectoryExists($directory);
            register_shutdown_function(fn () => (new Filesystem)->deleteDirectory($directory));

            self::$fontDirectory = $directory;
        }

        return self::$fontDirectory;
    }

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
     * @param  array<string, mixed>  $fields
     * @return TestResponse<\Illuminate\Http\Response>
     */
    public function saveLayoutForm(int $id, array $fields): TestResponse
    {
        return $this->post(Backend::url('renatio/dynamicpdf/layouts/update/' . $id), ['Layout' => $fields], [
            'X-AJAX-HANDLER' => 'onSave',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return TestResponse<\Illuminate\Http\Response>
     */
    public function previewUnsaved(string $definition, int $id, array $fields, string $mode = 'html'): TestResponse
    {
        $model = $definition === 'templates' ? 'Template' : 'Layout';

        return $this->post(Backend::url("renatio/dynamicpdf/{$definition}/update/{$id}"), [$model => $fields, 'mode' => $mode], [
            'X-AJAX-HANDLER' => 'onPreviewUnsaved',
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
     * @param  array<string, string>  $files  view name without extension => file content
     */
    public function writeViewFiles(string $namespace, array $files, ?string $directory = null): string
    {
        if ($directory === null) {
            $directory = $this->temporaryDirectory('views');
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
