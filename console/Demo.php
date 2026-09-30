<?php

namespace Renatio\DynamicPDF\Console;

use Illuminate\Console\Command;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Plugin;
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
        Parameter::set(Plugin::DEMO_PARAMETER, 1);

        PDFManager::forgetInstance();

        $sync = new SyncTemplates;
        $sync->handle();

        $failed = $sync->report()['failed'];

        if ($failed === []) {
            $this->info('The demo templates were enabled.');

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

        Template::whereIn('code', $plugin->registerPDFTemplates())->get()->each->delete();

        foreach ($plugin->registerPDFLayouts() as $layout) {
            Layout::where('code', $layout)->doesntHave('templates')->get()->each->delete();

            if (Layout::where('code', $layout)->exists()) {
                $this->warn("Kept {$layout}, templates still use it.");
            }
        }

        Parameter::set(Plugin::DEMO_PARAMETER, 0);

        $this->info('The demo templates were disabled.');

        return self::SUCCESS;
    }
}
