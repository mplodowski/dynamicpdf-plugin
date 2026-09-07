<?php

namespace Renatio\DynamicPDF;

use ArrayObject;
use Backend\Facades\Backend;
use Barryvdh\DomPDF\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use RainLab\Translate\Classes\ThemeScanner;
use Renatio\DynamicPDF\Classes\PDFWrapper;
use Renatio\DynamicPDF\Console\Check;
use Renatio\DynamicPDF\Console\Demo;
use Renatio\DynamicPDF\Console\Sync;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\PluginBase;
use System\Classes\PluginManager;
use System\Models\Parameter;

class Plugin extends PluginBase
{
    /**
     * @return array<string, string>
     */
    public function pluginDetails(): array
    {
        return [
            'name' => 'renatio.dynamicpdf::lang.plugin.name',
            'description' => 'renatio.dynamicpdf::lang.plugin.description',
            'author' => 'Renatio',
            'icon' => 'octo-icon-file-pdf-o',
            'homepage' => 'https://octobercms.com/plugin/renatio-dynamicpdf',
        ];
    }

    public function boot(): void
    {
        $this->app->register(ServiceProvider::class);

        $this->app->bind('dynamicpdf', fn (Application $app): PDFWrapper => new PDFWrapper(
            $app->make('dompdf'),
            $app->make('config'),
            $app->make('files'),
            $app->make('view'),
        ));

        if (config('dompdf.public_path') === null) {
            config(['dompdf.public_path' => public_path()]);
        }

        Event::listen('rainlab.translate.themeScanner.afterScan', function (ThemeScanner $scanner): void {
            $messages = [];

            foreach (Layout::all() as $layout) {
                $messages = array_merge($messages, $scanner->parseContent($layout->content_html));
            }

            foreach (Template::all() as $template) {
                $messages = array_merge($messages, $scanner->parseContent($template->content_html));
            }

            $scanner->importMessages($messages);
        });
    }

    public function register(): void
    {
        $this->app->scoped(Template::LAYOUT_CACHE, fn (): ArrayObject => new ArrayObject);

        $this->registerConsoleCommand('dynamicpdf:demo', Demo::class);
        $this->registerConsoleCommand('dynamicpdf:sync', Sync::class);
        $this->registerConsoleCommand('dynamicpdf:check', Check::class);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function registerPermissions(): array
    {
        return [
            'renatio.dynamicpdf.manage_templates' => [
                'label' => 'renatio.dynamicpdf::lang.permissions.manage_templates',
                'tab' => 'renatio.dynamicpdf::lang.permissions.tab',
            ],
            'renatio.dynamicpdf.manage_layouts' => [
                'label' => 'renatio.dynamicpdf::lang.permissions.manage_layouts',
                'tab' => 'renatio.dynamicpdf::lang.permissions.tab',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function registerMarkupTags(): array
    {
        if (PluginManager::instance()->exists('RainLab.Translate')) {
            return [];
        }

        return [
            'filters' => [
                '_' => ['Lang', 'get'],
                '__' => ['Lang', 'choice'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function registerSettings(): array
    {
        return [
            'templates' => [
                'label' => 'renatio.dynamicpdf::lang.menu.label',
                'category' => 'renatio.dynamicpdf::lang.menu.category',
                'icon' => 'octo-icon-file-pdf-o',
                'url' => Backend::url('renatio/dynamicpdf/templates'),
                'description' => 'renatio.dynamicpdf::lang.menu.description',
                'permissions' => ['renatio.dynamicpdf.manage_templates'],
            ],
        ];
    }

    /**
     * @return array<string>
     */
    public function registerPDFTemplates(): array
    {
        if (! Parameter::get('renatio::dynamicpdf.demo')) {
            return [];
        }

        return [
            'renatio.dynamicpdf::pdf.invoice',
            'renatio.dynamicpdf::pdf.header_and_footer',
        ];
    }

    /**
     * @return array<string>
     */
    public function registerPDFLayouts(): array
    {
        if (! Parameter::get('renatio::dynamicpdf.demo')) {
            return [];
        }

        return [
            'renatio.dynamicpdf::pdf.layouts.default',
            'renatio.dynamicpdf::pdf.layouts.header_and_footer',
        ];
    }
}
