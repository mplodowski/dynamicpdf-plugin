<?php

namespace Renatio\DynamicPDF\Console;

use Illuminate\Console\Command;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\PluginManager;
use System\Models\Parameter;

class Demo extends Command
{
    protected $signature = 'dynamicpdf:demo {--disable}';

    protected $description = 'Enable/Disable PDF demo templates.';

    public function handle(): int
    {
        return $this->option('disable') ? $this->disableDemo() : $this->enableDemo();
    }

    protected function enableDemo(): int
    {
        Parameter::set('renatio::dynamicpdf.demo', 1);

        PDFManager::forgetInstance();
        SyncTemplates::forgetFailures();

        $sync = new SyncTemplates;
        $sync->handle();

        $this->info(e(trans('renatio.dynamicpdf::lang.demo.enabled')));

        $failed = $sync->report()['failed'];

        if ($failed === []) {
            return self::SUCCESS;
        }

        $this->line('<fg=red;options=bold>Failed (see the application log)</>');

        foreach ($failed as $code) {
            $this->line("  {$code}");
        }

        return self::FAILURE;
    }

    protected function disableDemo(): int
    {
        $plugin = PluginManager::instance()->findByNamespace('Renatio.DynamicPDF');

        foreach ($plugin->registerPDFTemplates() as $template) {
            Template::where('code', $template)->delete();
        }

        foreach ($plugin->registerPDFLayouts() as $layout) {
            Layout::where('code', $layout)->get()->each->delete();
        }

        Parameter::set('renatio::dynamicpdf.demo', 0);

        $this->info(e(trans('renatio.dynamicpdf::lang.demo.disabled')));

        return self::SUCCESS;
    }
}
