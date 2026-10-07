<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class PDFAsset
{
    /**
     * Twig filters are shared by every environment, so the wrapper being rendered is
     * published here for the duration of its render only.
     */
    protected static ?PDFWrapper $rendering = null;

    /**
     * @param  callable(): string  $render
     */
    public static function whileRendering(PDFWrapper $pdf, callable $render): string
    {
        $previous = static::$rendering;
        static::$rendering = $pdf;

        try {
            return $render();
        } finally {
            static::$rendering = $previous;
        }
    }

    public function __invoke(mixed $path): string
    {
        $path = (string) $path;
        $pdf = static::$rendering;

        if ($pdf === null || $pdf->isForBrowser()) {
            return URL::to($path);
        }

        $local = $this->pluginFile($path);

        if ($local === null || ! $this->insideChroot($local, $pdf)) {
            Log::warning("Renatio.DynamicPDF pdfasset ignored [{$path}], it is not a file inside the plugins directory and the dompdf chroot.");

            return '';
        }

        return $local;
    }

    protected function pluginFile(string $path): ?string
    {
        if ($path === '' || str_contains($path, '://') || str_contains($path, "\0") || preg_match('~^([/\\\\]|[a-z]:)~i', $path)) {
            return null;
        }

        $file = realpath(base_path($path));
        $plugins = realpath(plugins_path());

        if ($file === false || $plugins === false || ! is_file($file) || ! str_starts_with($file, $plugins . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $file;
    }

    protected function insideChroot(string $file, PDFWrapper $pdf): bool
    {
        foreach ($pdf->getDomPDF()->getOptions()->getChroot() as $directory) {
            $directory = realpath((string) $directory);

            if ($directory !== false && str_starts_with($file, rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }
}
