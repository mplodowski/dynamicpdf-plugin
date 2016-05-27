<?php

namespace Renatio\DynamicPDF;

use App;
use System\Classes\PluginBase;
use Illuminate\Foundation\AliasLoader;
use Backend;
use Config;
use File;

/**
 * Class Plugin
 * @package Renatio\DynamicPDF
 */
class Plugin extends PluginBase
{

    /**
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'renatio.dynamicpdf::lang.plugin.name',
            'description' => 'renatio.dynamicpdf::lang.plugin.description',
            'author' => 'Renatio',
            'icon' => 'icon-file-pdf-o',
            'homepage' => 'http://octobercms.com/plugin/renatio-dynamicpdf'
        ];
    }

    /**
     * @return void
     */
    public function boot()
    {
        $this->registerPackage();

        $this->createFontDirectory();
    }

    /**
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'dynamicpdf' => [
                'label' => 'renatio.dynamicpdf::lang.menu.label',
                'url' => Backend::url('renatio/dynamicpdf/templates'),
                'icon' => 'icon-file-pdf-o',
                'permissions' => ['renatio.dynamicpdf.*'],
                'order' => 500,

                'sideMenu' => [
                    'templates' => [
                        'label' => 'renatio.dynamicpdf::lang.templates.templates',
                        'icon' => 'icon-file-text-o',
                        'url' => Backend::url('renatio/dynamicpdf/templates'),
                        'permissions' => ['renatio.dynamicpdf.manage_templates']
                    ],
                    'layouts' => [
                        'label' => 'renatio.dynamicpdf::lang.templates.layouts',
                        'icon' => 'icon-th-large',
                        'url' => Backend::url('renatio/dynamicpdf/layouts'),
                        'permissions' => ['renatio.dynamicpdf.manage_layouts']
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'renatio.dynamicpdf.manage_templates' => [
                'tab' => 'renatio.dynamicpdf::lang.permissions.tab',
                'label' => 'renatio.dynamicpdf::lang.permissions.manage_templates'
            ],
            'renatio.dynamicpdf.manage_layouts' => [
                'tab' => 'renatio.dynamicpdf::lang.permissions.tab',
                'label' => 'renatio.dynamicpdf::lang.permissions.manage_layouts'
            ]
        ];
    }

    /**
     * @return void
     */
    private function createFontDirectory()
    {
        $config = Config::get('dompdf.defines');

        if ( ! File::exists($config['DOMPDF_FONT_CACHE'])) {
            File::makeDirectory($config['DOMPDF_FONT_CACHE']);
        }
    }

    /**
     * @return void
     */
    private function registerPackage()
    {
        App::register('Barryvdh\DomPDF\ServiceProvider');
        App::register('Renatio\DynamicPDF\Classes\ServiceProvider');

        $alias = AliasLoader::getInstance();
        $alias->alias('PDF', 'Renatio\DynamicPDF\Classes\PDF');

        Config::set('dompdf.config_file', __DIR__ . '/vendor/dompdf/dompdf/dompdf_config.inc.php');
    }

}