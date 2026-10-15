<?php

namespace Renatio\DynamicPDF\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\ExportTemplates;
use Symfony\Component\Console\Output\OutputInterface;

class Export extends Command
{
    protected $signature = 'dynamicpdf:export
        {codes?* : Template or layout codes to export; everything when omitted}
        {--path= : File to write, or "-" for the standard output; defaults to storage/app/dynamicpdf-export-<date>.json}';

    protected $description = 'Export PDF templates and layouts, with their translations and background images, to a JSON file.';

    public function handle(): int
    {
        $export = new ExportTemplates;

        try {
            $payload = $export->handle($this->argument('codes'));
        } catch (ApplicationException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($export->missingBackgrounds() !== []) {
            $this->components->warn('Exported without the background image, its file is missing: ' . implode(', ', $export->missingBackgrounds()));
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $path = $this->option('path') ?: storage_path('app/dynamicpdf-export-' . now()->format('Y-m-d-His') . '.json');

        if ($path === '-') {
            $this->output->writeln($json, OutputInterface::OUTPUT_RAW);

            return self::SUCCESS;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $json . PHP_EOL);

        $this->components->info(sprintf('Exported %d templates and %d layouts to %s.', count($payload['templates']), count($payload['layouts']), $path));

        return self::SUCCESS;
    }
}
