<?php

namespace Renatio\DynamicPDF;

use Backend\Facades\Backend;
use Renatio\DynamicPDF\Classes\ServiceProvider;
use System\Classes\PluginBase;

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
        $this->app->register(ServiceProvider::class);
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

}