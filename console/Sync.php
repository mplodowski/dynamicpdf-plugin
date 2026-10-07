<?php

namespace Renatio\DynamicPDF\Console;

use Illuminate\Console\Command;
use Renatio\DynamicPDF\Classes\SyncTemplates;

class Sync extends Command
{
    protected $signature = 'dynamicpdf:sync {--prune : Delete the non-customised templates whose view is no longer registered}';

    protected $description = 'Synchronise the registered PDF views with the database and report the outcome.';

    public function handle(): int
    {
        $sync = new SyncTemplates;
        $sync->handle((bool) $this->option('prune'));
        $report = $sync->report();

        $this->list('Created', $report['created'], 'green');
        $this->list('Updated', $report['updated'], 'cyan');
        $this->list('Deleted', $report['deleted'], 'yellow');
        $this->list('No longer registered (run with --prune to delete)', $report['orphaned'], 'yellow');
        $this->list('Failed (see the application log)', $report['failed'], 'red');

        if (array_filter($report) === []) {
            $this->components->info('Everything is up to date.');
        }

        return $report['failed'] === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<int, string>  $codes
     */
    protected function list(string $label, array $codes, string $color): void
    {
        if ($codes === []) {
            return;
        }

        $this->line("<fg={$color};options=bold>{$label}</>");

        foreach ($codes as $code) {
            $this->line("  {$code}");
        }
    }
}
