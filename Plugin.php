<?php namespace Renatio\DynamicPDF;

use Illuminate\Foundation\AliasLoader;
use Config;
use File;
use System\Classes\PluginBase;
use Backend;

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
        ];
    }

    public function boot()
    {
        \App::register('Barryvdh\DomPDF\ServiceProvider');

        $alias = AliasLoader::getInstance();
        $alias->alias('PDF', 'Barryvdh\DomPDF\Facade');

        $this->publishes([
            __DIR__ . '/config/dompdf.php' => config_path('dompdf.php'),
        ]);

        $this->createFontDirectory();
    }

    /**
     * Register PDF templates in CMS settings
     *
     * @return array
     */
    public function registerSettings()
    {
        return [
            'pdftemplates' => [
                'label'       => 'renatio.dynamicpdf::lang.pdftemplates.label',
                'description' => 'renatio.dynamicpdf::lang.settings.description',
                'category'    => 'PDF',
                'icon'        => 'icon-file-pdf-o',
                'url'         => Backend::url('renatio/dynamicpdf/pdftemplates'),
                'order'       => 500,
                'keywords'    => 'pdf',
                'permissions' => ['renatio.dynamicpdf.manage_pdf_templates'],
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
            ],
        ];
    }

    /**
     * Create directory for cache fonts
     */
    private function createFontDirectory()
    {
        $config = Config::get('dompdf.defines');

        if ( ! File::exists($config['DOMPDF_FONT_CACHE']))
        {
            File::makeDirectory($config['DOMPDF_FONT_CACHE']);
        }
    }

}
