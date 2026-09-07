<?php

namespace Renatio\DynamicPDF\Classes;

use October\Rain\Support\Traits\Singleton;
use System\Classes\PluginBase;
use System\Classes\PluginManager;

class PDFManager
{
    use Singleton;

    /** @var array<string, string> */
    protected array $registeredTemplates = [];

    /** @var array<string, string> */
    protected array $registeredLayouts = [];

    /** @var array<string, mixed> */
    protected array $registeredVariables = [];

    protected bool $loaded = false;

    /**
     * Collects the registrations of every plugin once; direct register*() calls made before
     * that are kept alongside them.
     */
    public function loadRegisteredTemplates(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        foreach (PluginManager::instance()->getPlugins() as $plugin) {
            if (! $plugin instanceof PluginBase) {
                continue;
            }

            if (method_exists($plugin, 'registerPDFLayouts') && is_array($layouts = $plugin->registerPDFLayouts())) {
                $this->registerLayouts($layouts);
            }

            if (method_exists($plugin, 'registerPDFTemplates') && is_array($templates = $plugin->registerPDFTemplates())) {
                $this->registerTemplates($templates);
            }

            if (method_exists($plugin, 'registerPDFVariables') && is_array($variables = $plugin->registerPDFVariables())) {
                $this->registerVariables($variables);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function listRegisteredLayouts(): array
    {
        $this->loadRegisteredTemplates();

        return $this->registeredLayouts;
    }

    /**
     * @return array<string, string>
     */
    public function listRegisteredTemplates(): array
    {
        $this->loadRegisteredTemplates();

        return $this->registeredTemplates;
    }

    /**
     * Variables every template and layout receives. A closure value is resolved at render time.
     *
     * @return array<string, mixed>
     */
    public function listRegisteredVariables(): array
    {
        $this->loadRegisteredTemplates();

        return $this->registeredVariables;
    }

    /**
     * @param  array<string>  $definitions
     */
    public function registerLayouts(array $definitions): void
    {
        $this->registeredLayouts = array_combine($definitions, $definitions) + $this->registeredLayouts;
    }

    /**
     * @param  array<string>  $definitions
     */
    public function registerTemplates(array $definitions): void
    {
        $this->registeredTemplates = array_combine($definitions, $definitions) + $this->registeredTemplates;
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function registerVariables(array $variables): void
    {
        $this->registeredVariables = array_merge($this->registeredVariables, $variables);
    }
}
