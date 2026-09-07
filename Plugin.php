<?php

namespace Renatio\DynamicPDF;

use Backend\Facades\Backend;
use Barryvdh\DomPDF\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Renatio\DynamicPDF\Classes\PDFWrapper;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Console\Demo;
use System\Classes\PluginBase;
use System\Classes\PluginManager;
use System\Models\Parameter;

class Plugin extends PluginBase
{
    /**
     * @return array{name: string, description: string, author: string, icon: string, homepage: string}
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

        (new SyncTemplates)->handle();
    }

    public function register(): void
    {
        $this->registerConsoleCommand('dynamicpdf:demo', Demo::class);
    }

    /**
     * @return array<string, array{label: string, tab: string}>
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
     * @return array{filters?: array<string, array{0: string, 1: string}>}
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
     * @return array<string, array{label: string, category: string, icon: string, url: string, description: string, permissions: array<string>}>
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
