<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class PDFParser
{
    /**
     * @return array{settings: array<string, mixed>, css: string|null, html: string|null}
     */
    public static function parse(string $content): array
    {
        $sections = preg_split('/^={2,}\s*/m', $content, -1);
        $sections = array_map('trim', $sections);
        $count = count($sections);

        $result = [
            'settings' => [],
            'css' => null,
            'html' => null,
        ];

        if ($count >= 3) {
            $result['settings'] = parse_ini_string($sections[0], true) ?: [];
            $result['css'] = $sections[1];
            $result['html'] = $sections[2];
        } elseif ($count === 2) {
            $result['settings'] = parse_ini_string($sections[0], true) ?: [];
            $result['html'] = $sections[1];
        } elseif ($count === 1) {
            $result['html'] = $sections[0];
        }

        return $result;
    }

    /**
     * @return array{settings: array<string, mixed>, css: string|null, html: string|null}
     */
    public static function sections(string $path): array
    {
        return self::parse(File::get(View::make($path)->getPath()));
    }
}
