<?php

namespace Renatio\DynamicPDF\Classes;

use October\Rain\Support\Traits\Singleton;
use System\Classes\PluginBase;
use System\Classes\PluginManager;

class PDFManager
{
    use Singleton;

    /** @var array<string, string>|null */
    protected ?array $registeredTemplates = null;

    /** @var array<string, string>|null */
    protected ?array $registeredLayouts = null;

    /** @var array<string, mixed>|null */
    protected ?array $registeredVariables = null;

    public function loadRegisteredTemplates(): void
    {
        $plugins = PluginManager::instance()->getPlugins();

        foreach ($plugins as $plugin) {
            if (! $plugin instanceof PluginBase) {
                continue;
            }

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

            if (method_exists($plugin, 'registerPDFVariables')) {
                $variables = $plugin->registerPDFVariables();

                if (is_array($variables)) {
                    $this->registerVariables($variables);
                }
            }
        }
    }

    /**
     * Variables every template and layout receives. A closure value is resolved at render time.
     *
     * @return array<string, mixed>
     */
    public function listRegisteredVariables(): array
    {
        if ($this->registeredVariables === null) {
            $this->loadRegisteredTemplates();
        }

        return $this->registeredVariables ?? [];
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function registerVariables(array $variables): void
    {
        $this->registeredVariables = array_merge($this->registeredVariables ?? [], $variables);
    }

    /**
     * @return array<string, string>|null
     */
    public function listRegisteredLayouts(): ?array
    {
        if ($this->registeredLayouts === null) {
            $this->loadRegisteredTemplates();
        }

        return $this->registeredLayouts;
    }

    /**
     * @return array<string, string>|null
     */
    public function listRegisteredTemplates(): ?array
    {
        if ($this->registeredTemplates === null) {
            $this->loadRegisteredTemplates();
        }

        return $this->registeredTemplates;
    }

    /**
     * @param  array<string>  $definitions
     */
    public function registerLayouts(array $definitions): void
    {
        $this->registeredLayouts ??= [];

        $definitions = array_combine($definitions, $definitions);

        $this->registeredLayouts = $definitions + $this->registeredLayouts;
    }

    /**
     * @param  array<string>  $definitions
     */
    public function registerTemplates(array $definitions): void
    {
        $this->registeredTemplates ??= [];

        $definitions = array_combine($definitions, $definitions);

        $this->registeredTemplates = $definitions + $this->registeredTemplates;
    }
}
