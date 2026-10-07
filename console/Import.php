<?php

namespace Renatio\DynamicPDF\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\ImportTemplates;

class Import extends Command
{
    protected $signature = 'dynamicpdf:import
        {file : JSON file written by dynamicpdf:export}
        {--force : Overwrite templates and layouts whose code already exists}';

    protected $description = 'Import PDF templates and layouts from a dynamicpdf:export file in one transaction.';

    public function handle(): int
    {
        $path = (string) $this->argument('file');

        if (! File::isFile($path)) {
            $this->components->error("The file {$path} does not exist.");

            return self::FAILURE;
        }

        $import = new ImportTemplates;

        try {
            $payload = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
            $import->handle(is_array($payload) ? $payload : [], (bool) $this->option('force'));
        } catch (JsonException) {
            $this->components->error("The file {$path} is not valid JSON.");

            return self::FAILURE;
        } catch (ApplicationException $e) {
            $this->components->error($e->getMessage() . ' Nothing was imported.');

            return self::FAILURE;
        }

        $report = $import->report();

        $this->table(['Type', 'Code', 'Result'], $report);

        if (in_array('skipped', array_column($report, 2), true)) {
            $this->components->warn('Skipped codes already exist; run with --force to overwrite them.');
        }

        return self::SUCCESS;
    }
}
