<?php

namespace Renatio\DynamicPDF;

use Backend\Facades\Backend;
use Barryvdh\DomPDF\ServiceProvider;
use Renatio\DynamicPDF\Classes\PDFWrapper;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Console\Demo;
use System\Classes\PluginBase;
use System\Classes\PluginManager;
use System\Models\Parameter;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name' => 'renatio.dynamicpdf::lang.plugin.name',
            'description' => 'renatio.dynamicpdf::lang.plugin.description',
            'author' => 'Renatio',
            'icon' => 'octo-icon-file-pdf-o',
            'homepage' => 'https://octobercms.com/plugin/renatio-dynamicpdf',
        ];
    }

    public function boot()
    {
        $this->app->register(ServiceProvider::class);

        $this->app->bind('dynamicpdf', function ($app) {
            return new PDFWrapper($app['dompdf'], $app['config'], $app['files'], $app['view']);
        });

        if (config('dompdf.public_path') === null) {
            config(['dompdf.public_path' => public_path()]);
        }

        (new SyncTemplates)->handle();
    }

    public function register()
    {
        $this->registerConsoleCommand('dynamicpdf:demo', Demo::class);
    }

    public function registerPermissions()
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

    public function registerMarkupTags()
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

    public function registerSettings()
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

    public function registerPDFTemplates()
    {
        if (! Parameter::get('renatio::dynamicpdf.demo')) {
            return [];
        }

        return [
            'renatio.dynamicpdf::pdf.invoice',
            'renatio.dynamicpdf::pdf.header_and_footer',
        ];
    }

    public function registerPDFLayouts()
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
