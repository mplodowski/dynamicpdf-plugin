<?php

namespace Renatio\DynamicPDF\Classes;

use File;
use View;

class PDFParser
{
    public static function parse($content)
    {
        $sections = preg_split('/^={2,}\s*/m', $content, -1);
        $count = count($sections);

        foreach ($sections as &$section) {
            $section = trim($section);
        }

        $result = [
            'settings' => [],
            'css' => null,
            'html' => null,
        ];

        if ($count >= 3) {
            $result['settings'] = parse_ini_string($sections[0], true);
            $result['css'] = $sections[1];
            $result['html'] = $sections[2];
        } elseif ($count == 2) {
            $result['settings'] = parse_ini_string($sections[0], true);
            $result['html'] = $sections[1];
        } elseif ($count == 1) {
            $result['html'] = $sections[0];
        }

        return $result;
    }

    public static function sections($path)
    {
        return PDFParser::parse(File::get(View::make($path)->getPath()));
    }
}
