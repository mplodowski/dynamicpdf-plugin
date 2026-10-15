<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Facades\URL;

/**
 * The sandboxed HTML preview has an opaque origin, so browsers refuse cross-origin font
 * requests to the application; its own public fonts are embedded instead.
 */
class PreviewFonts
{
    protected const MAX_BYTES = 5 * 1024 * 1024;

    protected const MAX_TOTAL_BYTES = 20 * 1024 * 1024;

    protected const MIME_TYPES = [
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    /**
     * The directories October's web server rules serve; anything else stays a plain URL.
     */
    protected const PUBLIC_DIRECTORIES = [
        '~^plugins/[^/]+/[^/]+/(assets|resources)/~',
        '~^themes/[^/]+/(assets|resources)/~',
        '~^storage/app/media/~',
        '~^storage/app/uploads/public/~',
    ];

    /**
     * @var array<int, string>|null
     */
    protected ?array $hosts = null;

    /** @var array<string, string|null> */
    protected array $dataUris = [];

    protected int $inlinedBytes = 0;

    /**
     * A regex that gives up on a huge document (backtrack limit) leaves the HTML unchanged.
     */
    public function inline(string $html): string
    {
        return preg_replace_callback(
            '~(<style\b[^>]*>)(.*?)(</style>)~is',
            fn (array $style): string => $style[1] . $this->inlineStyle($style[2]) . $style[3],
            $html,
        ) ?? $html;
    }

    protected function inlineStyle(string $css): string
    {
        return preg_replace_callback(
            '~@font-face\s*\{[^}]*\}~i',
            fn (array $rule): string => $this->inlineFontFace($rule[0]),
            $css,
        ) ?? $css;
    }

    protected function inlineFontFace(string $rule): string
    {
        return preg_replace_callback(
            '~url\(\s*(["\']?)([^"\')\s]+)\1\s*\)~i',
            function (array $match): string {
                [$declaration, $quote, $url] = $match;
                $data = $this->dataUri($url);

                return $data === null ? $declaration : "url({$quote}{$data}{$quote})";
            },
            $rule,
        ) ?? $rule;
    }

    protected function dataUri(string $url): ?string
    {
        $path = $this->localPath($url);

        if ($path === null) {
            return null;
        }

        return $this->dataUris[$path] ??= $this->readFont($path);
    }

    protected function readFont(string $path): ?string
    {
        $mime = self::MIME_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        $file = base_path($path);
        $size = is_file($file) && is_readable($file) ? filesize($file) : false;

        if ($mime === null || $size === false || $size > self::MAX_BYTES || $this->inlinedBytes + $size > self::MAX_TOTAL_BYTES) {
            return null;
        }

        $contents = @file_get_contents($file);

        if ($contents === false) {
            return null;
        }

        $this->inlinedBytes += $size;

        return "data:{$mime};base64," . base64_encode($contents);
    }

    /**
     * The path is checked before symlinks are resolved, so a symlinked plugin is accepted.
     */
    protected function localPath(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || (isset($parts['scheme']) && ! in_array(strtolower($parts['scheme']), ['http', 'https'], true))) {
            return null;
        }

        if (isset($parts['host'])) {
            if (! in_array(strtolower((string) $parts['host']), $this->hosts(), true)) {
                return null;
            }
        } elseif (! str_starts_with($url, '/')) {
            return null;
        }

        $base = rtrim((string) parse_url(URL::to('/'), PHP_URL_PATH), '/') . '/';
        $path = rawurldecode($parts['path'] ?? '');

        if (! str_starts_with($path, $base)) {
            return null;
        }

        $path = substr($path, strlen($base));

        if (str_contains($path, "\0") || str_contains($path, '\\') || in_array('..', explode('/', $path), true)) {
            return null;
        }

        foreach (self::PUBLIC_DIRECTORIES as $directory) {
            if (preg_match($directory, $path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function hosts(): array
    {
        return $this->hosts ??= array_values(array_unique(array_filter([
            ...(new RemoteAssetPolicy)->applicationHosts(),
            strtolower((string) parse_url(URL::to('/'), PHP_URL_HOST)),
            strtolower(request()->getHost()),
        ])));
    }
}
