<?php

namespace Renatio\DynamicPDF;

use Backend\Facades\Backend;
use Renatio\DynamicPDF\Classes\ServiceProvider;
use System\Classes\PluginBase;
use System\Classes\PluginManager;

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
            'homepage' => 'http://octobercms.com/plugin/renatio-dynamicpdf',
        ];
    }

    /**
     * @return void
     */
    public function boot()
    {
        $this->app->register(ServiceProvider::class);
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    public function registerSettings()
    {
        return [
            'templates' => [
                'label' => 'renatio.dynamicpdf::lang.menu.label',
                'category' => 'renatio.dynamicpdf::lang.menu.category',
                'icon' => 'icon-file-pdf-o',
                'url' => Backend::url('renatio/dynamicpdf/templates'),
                'description' => 'renatio.dynamicpdf::lang.menu.description',
                'permissions' => ['renatio.dynamicpdf.manage_templates'],
            ],
        ];
    }
}
