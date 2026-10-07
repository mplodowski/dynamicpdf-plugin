<?php

namespace Renatio\DynamicPDF\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use stdClass;
use Throwable;

class Render extends Command
{
    protected $signature = 'dynamicpdf:render
        {code : Template code}
        {--data= : Template variables as a JSON object, or @path/to/file.json}
        {--sample : Use the sample data saved on the template; --data keys override it}
        {--layout= : Layout code to render with instead of the template\'s own}
        {--locale= : Locale to render in}
        {--output= : File to write; defaults to storage/temp/<code>.pdf}
        {--html : Write the HTML passed to dompdf, with asset URLs, instead of the PDF}';

    protected $description = 'Render a PDF template to a file with the production configuration.';

    public function handle(): int
    {
        $code = (string) $this->argument('code');
        $html = (bool) $this->option('html');
        $path = $this->stringOption('output') ?? $this->defaultPath($code, $html);

        $GLOBALS['_dompdf_warnings'] = [];

        try {
            $pdf = PDF::forBrowser($html)
                ->loadTemplate($code, $this->data($code), layout: $this->stringOption('layout'), locale: $this->stringOption('locale'));

            File::ensureDirectoryExists(dirname($path));
            File::put($path, $html ? $pdf->getDomPDF()->outputHtml() : $pdf->output());
        } catch (Throwable $e) {
            if ($this->output->isVerbose()) {
                throw $e;
            }

            $this->components->error($this->failureMessage($e));

            return self::FAILURE;
        }

        foreach (array_unique((array) $GLOBALS['_dompdf_warnings']) as $warning) {
            $this->components->warn((string) $warning);
        }

        $this->components->info("Rendered {$code} to {$path}.");

        return self::SUCCESS;
    }

    protected function failureMessage(Throwable $e): string
    {
        if ($e instanceof ModelNotFoundException && in_array($e->getModel(), [Template::class, Layout::class], true)) {
            $type = $e->getModel() === Layout::class ? 'layout' : 'template';

            return sprintf('The %s %s does not exist.', $type, implode(', ', $e->getIds()));
        }

        if ($e instanceof InvalidArgumentException) {
            return $e->getMessage();
        }

        return trim(class_basename($e) . ': ' . $e->getMessage(), ': ') . ' Run with -v for the trace.';
    }

    protected function defaultPath(string $code, bool $html): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $code);

        return storage_path("temp/{$name}" . ($html ? '.html' : '.pdf'));
    }

    protected function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function data(string $code): array
    {
        $sample = $this->option('sample') ? Template::byCode($code)->sampleData() : [];
        $json = $this->stringOption('data');

        if ($json === null) {
            return $sample;
        }

        if (str_starts_with($json, '@')) {
            $file = substr($json, 1);

            if (! File::isFile($file) || ! File::isReadable($file)) {
                throw new InvalidArgumentException("The data file {$file} does not exist or is not readable.");
            }

            $json = File::get($file);
        }

        try {
            $data = json_decode($json, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException("The --data value is not valid JSON: {$e->getMessage()}.");
        }

        if (! $data instanceof stdClass) {
            throw new InvalidArgumentException('The --data value must be a JSON object.');
        }

        return array_replace($sample, (array) json_decode($json, true));
    }
}
