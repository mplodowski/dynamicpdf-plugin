<?php

namespace Renatio\DynamicPDF\Classes;

use File;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use View;

/**
 * Class PDFParser
 * @package Renatio\DynamicPDF\Classes
 */
class PDFParser
{

    const SECTION_SEPARATOR = '==';

    /**
     * Parses PDF template content.
     * The expected file format is following:
     * <pre>
     * Settings section
     * ==
     * CSS content section
     * ==
     * HTML content section
     * </pre>
     * If the content has only 2 sections they are considered as settings and HTML.
     * If there is only a single section, it is considered as HTML.
     * @param  string  $content  Specifies the file content.
     * @return array Returns an array with the following indexes: 'settings', 'css', 'html'.
     * The 'html' and 'css' elements contain strings. The 'settings' element contains the
     * parsed INI file as array. If the content string doesn't contain a section, the corresponding
     * result element has null value.
     */
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

    /**
     * @param $path
     * @return array
     * @throws FileNotFoundException
     */
    public static function sections($path)
    {
        return PDFParser::parse(File::get(View::make($path)->getPath()));
    }
}
