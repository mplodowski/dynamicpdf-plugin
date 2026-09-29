<?php

namespace Renatio\DynamicPDF\Classes;

use Exception;
use Less_Parser;
use Less_Tree;
use Less_Tree_Call;
use Less_Tree_Import;
use October\Rain\Exception\ApplicationException;

/**
 * Compiles layout LESS without letting it read server files. less.php resolves imports and
 * the file functions in PHP, before dompdf and its chroot see anything, so the parsed tree
 * is checked first. Only plain CSS imports of remote stylesheets pass, which less.php
 * leaves to dompdf untouched.
 */
class LessCompiler extends Less_Parser
{
    private const FILE_FUNCTIONS = ['data-uri', 'image-size', 'image-width', 'image-height'];

    /**
     * The messages stay generic: a parser error quotes the source it failed on.
     *
     * @throws ApplicationException
     */
    public static function compile(string $less): string
    {
        $parser = self::parsed($less);

        if ($parser === null) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.layout.css_invalid')));
        }

        if ($parser->readsFiles()) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.layout.css_reads_files')));
        }

        try {
            return $parser->getCss();
        } catch (Exception) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.layout.css_invalid')));
        }
    }

    /**
     * LESS that does not parse cannot read anything; compile() reports it at render time.
     */
    public static function canReadFiles(string $less): bool
    {
        return (bool) self::parsed($less)?->readsFiles();
    }

    private static function parsed(string $less): ?self
    {
        $parser = new self;

        try {
            $parser->parse($less);
        } catch (Exception) {
            return null;
        }

        return $parser;
    }

    private function readsFiles(): bool
    {
        $seen = [];

        return $this->containsFileAccess($this->rules, $seen);
    }

    /**
     * Walks every public property rather than trusting each node's accept(), so an import
     * nested in a mixin, a media block or a detached ruleset is found as well.
     *
     * @param  array<int, true>  $seen
     */
    private function containsFileAccess(mixed $value, array &$seen): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsFileAccess($item, $seen)) {
                    return true;
                }
            }

            return false;
        }

        if (! $value instanceof Less_Tree || isset($seen[spl_object_id($value)])) {
            return false;
        }

        $seen[spl_object_id($value)] = true;

        if ($value instanceof Less_Tree_Import && ! self::isRemoteCssImport($value)) {
            return true;
        }

        if ($value instanceof Less_Tree_Call && in_array(strtolower((string) $value->name), self::FILE_FUNCTIONS, true)) {
            return true;
        }

        return $this->containsFileAccess(get_object_vars($value), $seen);
    }

    private static function isRemoteCssImport(Less_Tree_Import $import): bool
    {
        return $import->css === true
            && ! $import->options['inline']
            && preg_match('#^https?://#i', (string) $import->getPath()) === 1;
    }
}
