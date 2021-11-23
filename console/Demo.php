<?php

namespace Renatio\DynamicPDF\Console;

use Illuminate\Console\Command;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\PluginManager;
use System\Models\Parameter;

class Demo extends Command
{
    protected $signature = 'dynamicpdf:demo {--disable}';

    protected $description = 'Enable/Disable PDF demo templates.';

    public function handle()
    {
        if ($this->option('disable')) {
            $this->disableDemo();
        } else {
            $this->enableDemo();
        }
    }

    protected function enableDemo()
    {
        Parameter::set('renatio::dynamicpdf.demo', 1);

        $this->info(e(trans('renatio.dynamicpdf::lang.demo.enabled')));
    }

    protected function disableDemo()
    {
        $plugin = PluginManager::instance()->findByNamespace('Renatio.DynamicPDF');

        foreach ($plugin->registerPDFTemplates() as $template) {
            Template::where('code', $template)->delete();
        }

        foreach ($plugin->registerPDFLayouts() as $layout) {
            Layout::where('code', $layout)->delete();
        }

        Parameter::set('renatio::dynamicpdf.demo', 0);

        $this->info(e(trans('renatio.dynamicpdf::lang.demo.disabled')));
    }
}
