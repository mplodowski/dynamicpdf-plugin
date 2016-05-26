<?php namespace Renatio\DynamicPDF;

use System\Classes\PluginBase;
use Illuminate\Foundation\AliasLoader;
use Backend;
use Config;
use File;

/**
 * DynamicPDF Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'renatio.dynamicpdf::lang.plugin.name',
            'description' => 'renatio.dynamicpdf::lang.plugin.description',
            'author'      => 'Renatio',
            'icon'        => 'icon-file-pdf-o',
            'homepage'    => 'https://github.com/mplodowski/DynamicPDF'
        ];
    }

    public function boot()
    {
        \App::register('Barryvdh\DomPDF\ServiceProvider');

        $alias = AliasLoader::getInstance();
        $alias->alias('PDF', 'Barryvdh\DomPDF\Facade');

        $this->publishes([
            __DIR__ . '/config/dompdf.php' => config_path('dompdf.php')
        ]);

        $this->createFontDirectory();
    }

    public function registerNavigation()
    {
        return [
            'dynamicpdf' => [
                'label'       => 'renatio.dynamicpdf::lang.menu.label',
                'url'         => Backend::url('renatio/dynamicpdf/pdftemplates'),
                'icon'        => 'icon-file-pdf-o',
                'permissions' => ['renatio.dynamicpdf.*'],
                'order'       => 500,

                'sideMenu' => [
                    'templates' => [
                        'label'       => 'renatio.dynamicpdf::lang.pdftemplates.templates',
                        'icon'        => 'icon-file-text-o',
                        'url'         => Backend::url('renatio/dynamicpdf/pdftemplates'),
                        'permissions' => ['renatio.dynamicpdf.manage_pdf_templates']
                    ],
                    'layouts' => [
                        'label'       => 'renatio.dynamicpdf::lang.pdftemplates.layouts',
                        'icon'        => 'icon-th-large',
                        'url'         => Backend::url('renatio/dynamicpdf/pdflayouts'),
                        'permissions' => ['renatio.dynamicpdf.manage_pdf_templates']
                    ]
                ]
            ]
        ];
    }

    /**
     * Register permissions
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'renatio.dynamicpdf.manage_pdf_templates' => [
                'tab'   => 'renatio.dynamicpdf::lang.permissions.tab',
                'label' => 'renatio.dynamicpdf::lang.permissions.label'
            ]
        ];
    }

    /**
     * Create directory for cache fonts
     */
    private function createFontDirectory()
    {
        $config = Config::get('dompdf.defines');

        if (!File::exists($config['DOMPDF_FONT_CACHE'])) {
            File::makeDirectory($config['DOMPDF_FONT_CACHE']);
        }
    }
}
