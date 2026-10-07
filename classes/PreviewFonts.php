<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Facades\URL;

/**
 * The sandboxed HTML preview has an opaque origin, so browsers refuse cross-origin font
 * requests to the application; its own public fonts are embedded instead.
 */
class PreviewFonts
{
    const MAX_BYTES = 5 * 1024 * 1024;

    const MIME_TYPES = [
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    const PUBLIC_DIRECTORIES = [
        'plugins/',
        'themes/',
        'storage/app/media/',
        'storage/app/uploads/public/',
    ];

    /**
     * @var array<int, string>|null
     */
    protected ?array $hosts = null;

    public function inline(string $html): string
    {
        return (string) preg_replace_callback(
            '~(<style\b[^>]*>)(.*?)(</style>)~is',
            fn (array $style): string => $style[1] . $this->inlineStyle($style[2]) . $style[3],
            $html,
        );
    }

    protected function inlineStyle(string $css): string
    {
        return (string) preg_replace_callback(
            '~@font-face\s*\{[^}]*\}~i',
            fn (array $rule): string => $this->inlineFontFace($rule[0]),
            $css,
        );
    }

    protected function inlineFontFace(string $rule): string
    {
        return (string) preg_replace_callback(
            '~url\(\s*(["\']?)([^"\')\s]+)\1\s*\)~i',
            function (array $match): string {
                [$declaration, $quote, $url] = $match;
                $data = $this->dataUri($url);

                return $data === null ? $declaration : "url({$quote}{$data}{$quote})";
            },
            $rule,
        );
    }

    protected function dataUri(string $url): ?string
    {
        $path = $this->localPath($url);

        if ($path === null) {
            return null;
        }

        $mime = self::MIME_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        $file = base_path($path);

        if ($mime === null || ! is_file($file) || filesize($file) > self::MAX_BYTES) {
            return null;
        }

        return "data:{$mime};base64," . base64_encode((string) file_get_contents($file));
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
            if (! in_array($this->authority($parts), $this->hosts(), true)) {
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
            if (str_starts_with($path, $directory)) {
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
        if ($this->hosts === null) {
            $urls = [URL::to('/'), (string) config('app.url'), request()->getSchemeAndHttpHost()];
            $authorities = array_map(fn (string $url): ?string => $this->authority(parse_url($url)), $urls);

            $this->hosts = array_values(array_unique(array_filter($authorities)));
        }

        return $this->hosts;
    }

    /**
     * @param  array<string, int|string>|false  $parts
     */
    protected function authority(array|false $parts): ?string
    {
        if (empty($parts['host'])) {
            return null;
        }

        return strtolower((string) $parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
