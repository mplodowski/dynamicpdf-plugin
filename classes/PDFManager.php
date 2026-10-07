<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use October\Rain\Support\Traits\Singleton;
use System\Classes\PluginBase;
use System\Classes\PluginManager;
use System\Classes\SiteManager;
use Throwable;

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

    /** @var array<string, true> */
    protected array $loggedFailures = [];

    /**
     * @deprecated Use loadRegistrations().
     */
    public function loadRegisteredTemplates(): void
    {
        $this->loadRegistrations();
    }

    public function loadRegistrations(): void
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
        $this->loadRegistrations();

        return $this->registeredLayouts;
    }

    /**
     * @return array<string, string>
     */
    public function listRegisteredTemplates(): array
    {
        $this->loadRegistrations();

        return $this->registeredTemplates;
    }

    /**
     * @return array<string, mixed>
     */
    public function listRegisteredVariables(): array
    {
        $this->loadRegistrations();

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
     * Every backend list display and every fetch of the record hit the same broken view again.
     */
    public function logFailureOnce(string $model, string $code, string $message, Throwable $e): void
    {
        $key = "{$model}|{$code}|{$message}";

        if (isset($this->loggedFailures[$key])) {
            return;
        }

        $this->loggedFailures[$key] = true;

        Log::error($message, ['exception' => $e]);
    }

    public function findLocalizedView(string $view, string $locale): ?string
    {
        foreach (SiteManager::instance()->getLocaleKeyChain($locale) as $localeKey) {
            $candidate = $this->makeLocalizedViewName($view, $localeKey);

            if (View::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function makeLocalizedViewName(string $view, string $locale): string
    {
        $namespace = '';

        if (($position = strpos($view, '::')) !== false) {
            $namespace = substr($view, 0, $position + 2);
            $view = substr($view, $position + 2);
        }

        $segments = explode('.', $view);
        array_splice($segments, -1, 0, $locale);

        return $namespace . implode('.', $segments);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function registerVariables(array $variables): void
    {
        $this->registeredVariables = array_merge($this->registeredVariables, $variables);
    }
}
