<?php

namespace Renatio\DynamicPDF\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;
use Renatio\DynamicPDF\Classes\PDFManager;

class Check extends Command
{
    protected const PASS = '<fg=green;options=bold>PASS</>';

    protected const WARN = '<fg=yellow;options=bold>WARN</>';

    protected const FAIL = '<fg=red;options=bold>FAIL</>';

    protected $signature = 'dynamicpdf:check';

    protected $description = 'Check the dompdf configuration and the registered PDF views for problems that fail silently.';

    protected bool $failed = false;

    public function handle(): int
    {
        $this->newLine();

        $this->directory('Font directory', config('dompdf.options.font_dir'));
        $this->directory('Font cache', config('dompdf.options.font_cache'));
        $this->directory('Temporary directory', config('dompdf.options.temp_dir'));

        $this->components->twoColumnDetail('Chroot', implode(', ', (array) config('dompdf.options.chroot')));

        $this->components->twoColumnDetail(
            'Inline PHP (enable_php)',
            config('dompdf.options.enable_php') ? self::WARN . ' enabled: templates can run PHP on the server' : self::PASS . ' disabled',
        );

        $this->remote(config('dompdf.options.enable_remote'), config('dompdf.options.allowed_remote_hosts'));

        $this->views('Registered layouts', PDFManager::instance()->listRegisteredLayouts() ?? []);
        $this->views('Registered templates', PDFManager::instance()->listRegisteredTemplates() ?? []);

        $this->newLine();

        return $this->failed ? self::FAILURE : self::SUCCESS;
    }

    protected function remote(mixed $enabled, mixed $hosts): void
    {
        $label = 'Remote resources (enable_remote)';

        if (! $enabled) {
            $this->components->twoColumnDetail($label, self::PASS . ' disabled');

            return;
        }

        if ($hosts !== null && ! is_array($hosts)) {
            $this->components->twoColumnDetail($label, self::WARN . ' allowed_remote_hosts must be an array; dompdf ignores it and allows any host');

            return;
        }

        $this->components->twoColumnDetail(
            $label,
            $hosts ? self::PASS . ' enabled for ' . implode(', ', $hosts) : self::WARN . ' enabled for any host',
        );
    }

    /**
     * Only reports: creating the directory here would give it the CLI user's ownership and
     * certify a directory the web server still cannot write to.
     */
    protected function directory(string $label, mixed $path): void
    {
        if (! is_string($path) || $path === '') {
            $this->components->twoColumnDetail($label, self::WARN . ' not configured');

            return;
        }

        if (! is_dir($path)) {
            $this->report($label, "{$path} does not exist; create it and make it writable for the web server");

            return;
        }

        if (! is_writable($path)) {
            $this->report($label, "{$path} is not writable");

            return;
        }

        $this->components->twoColumnDetail($label, self::PASS . " {$path}");
    }

    /**
     * @param  array<string, string>  $codes
     */
    protected function views(string $label, array $codes): void
    {
        $missing = array_filter($codes, function (string $code): bool {
            try {
                View::getFinder()->find($code);

                return false;
            } catch (InvalidArgumentException) {
                return true;
            }
        });

        if ($missing === []) {
            $this->components->twoColumnDetail($label, self::PASS . ' ' . count($codes) . ' with a view file');

            return;
        }

        $this->report($label, count($missing) . ' without a view file');

        foreach ($missing as $code) {
            $this->line("  <fg=red>{$code}</>");
        }
    }

    protected function report(string $label, string $message): void
    {
        $this->failed = true;
        $this->components->twoColumnDetail($label, self::FAIL . " {$message}");
    }
}
