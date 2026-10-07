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
            return e(URL::to($path));
        }

        $file = $this->pluginFile($path);

        if ($file === null) {
            Log::warning("Renatio.DynamicPDF pdfasset ignored [{$path}], it is not a file inside the plugins directory.");

            return '';
        }

        return e($file);
    }

    /**
     * The path is checked before symlinks are resolved, so a symlinked plugin is accepted;
     * dompdf still applies its chroot to the real location.
     */
    protected function pluginFile(string $path): ?string
    {
        $segments = preg_split('~[/\\\\]~', $path) ?: [];

        if ($path === '' || str_contains($path, '://') || str_contains($path, "\0") || preg_match('~^([/\\\\]|[a-z]:)~i', $path) || in_array('..', $segments, true)) {
            return null;
        }

        $file = base_path($path);

        return str_starts_with($file, plugins_path() . DIRECTORY_SEPARATOR) && is_file($file) ? $file : null;
    }
}
