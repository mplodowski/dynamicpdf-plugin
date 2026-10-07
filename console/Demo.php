<?php

namespace Renatio\DynamicPDF\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Plugin;
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
        /** @var Collection<int, Template> $templates */
        $templates = Template::whereIn('code', Plugin::DEMO_TEMPLATES)->get();

        foreach ($templates as $template) {
            if ($template->is_custom) {
                $this->warn("Kept {$template->code}, it was customized.");

                continue;
            }

            $template->delete();
        }

        /** @var Collection<int, Layout> $layouts */
        $layouts = Layout::whereIn('code', Plugin::DEMO_LAYOUTS)->get();

        foreach ($layouts as $layout) {
            if (! $layout->is_locked || Template::where('layout_id', $layout->id)->exists()) {
                $layout->is_locked = false;
                $layout->forceSave();
                $this->warn("Kept {$layout->code}, templates still use it or it was customized.");

                continue;
            }

            $layout->delete();
        }

        Parameter::set(Plugin::DEMO_PARAMETER, 0);

        PDFManager::forgetInstance();

        $this->info('The demo templates were disabled.');

        return self::SUCCESS;
    }
}
