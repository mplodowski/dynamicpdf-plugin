<?php

namespace Renatio\DynamicPDF\Classes;

use October\Rain\Support\Traits\Singleton;
use System\Classes\PluginManager;

class PDFManager
{
    use Singleton;

    protected $registeredTemplates;

    protected $registeredLayouts;

    public function loadRegisteredTemplates()
    {
        $plugins = PluginManager::instance()->getPlugins();

        foreach ($plugins as $plugin) {
            if (method_exists($plugin, 'registerPDFLayouts')) {
                $layouts = $plugin->registerPDFLayouts();

                if (is_array($layouts)) {
                    $this->registerLayouts($layouts);
                }
            }

            if (method_exists($plugin, 'registerPDFTemplates')) {
                $templates = $plugin->registerPDFTemplates();

                if (is_array($templates)) {
                    $this->registerTemplates($templates);
                }
            }
        }
    }

    public function listRegisteredLayouts()
    {
        if ($this->registeredLayouts === null) {
            $this->loadRegisteredTemplates();
        }

        return $this->registeredLayouts;
    }

    public function listRegisteredTemplates()
    {
        if ($this->registeredTemplates === null) {
            $this->loadRegisteredTemplates();
        }

        return $this->registeredTemplates;
    }

    public function registerLayouts(array $definitions)
    {
        if (! $this->registeredLayouts) {
            $this->registeredLayouts = [];
        }

        $definitions = array_combine($definitions, $definitions);

        $this->registeredLayouts = $definitions + $this->registeredLayouts;
    }

    public function registerTemplates(array $definitions)
    {
        if (! $this->registeredTemplates) {
            $this->registeredTemplates = [];
        }

        $definitions = array_combine($definitions, $definitions);

        $this->registeredTemplates = $definitions + $this->registeredTemplates;
    }
}
