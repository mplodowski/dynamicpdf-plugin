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
 * is checked first. Plain CSS imports pass: less.php leaves them to dompdf untouched.
 */
class LessCompiler
{
    /**
     * less.php also accepts these without their hyphens, so names are compared hyphen-less.
     */
    protected const FILE_FUNCTIONS = ['datauri', 'imagesize', 'imagewidth', 'imageheight'];

    /**
     * @throws ApplicationException
     */
    public function compile(string $less): string
    {
        $parser = new Less_Parser;

        try {
            $parser->parse($less);

            $seen = [];

            if ($this->readsFiles((fn () => $this->rules)->call($parser), $seen)) {
                throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.layout.css_reads_files')));
            }

            return $parser->getCss();
        } catch (ApplicationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.layout.css_invalid') . ' ' . $this->describe($e)));
        }
    }

    /**
     * Walks every public property rather than trusting each node's accept(), so an import
     * nested in a mixin, a media block or a detached ruleset is found as well. An import
     * less.php leaves as CSS is never read, the same test its ImportVisitor applies.
     *
     * @param  array<int, true>  $seen
     */
    protected function readsFiles(mixed $value, array &$seen): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->readsFiles($item, $seen)) {
                    return true;
                }
            }

            return false;
        }

        if (! $value instanceof Less_Tree || isset($seen[spl_object_id($value)])) {
            return false;
        }

        $seen[spl_object_id($value)] = true;

        if ($value instanceof Less_Tree_Import && ($value->css !== true || $value->options['inline'])) {
            return true;
        }

        if ($value instanceof Less_Tree_Call && in_array(str_replace('-', '', strtolower((string) $value->name)), self::FILE_FUNCTIONS, true)) {
            return true;
        }

        return $this->readsFiles(get_object_vars($value), $seen);
    }

    /**
     * Only the first line: the parser appends a source excerpt and an internal file name.
     */
    protected function describe(Exception $e): string
    {
        $message = strtok($e->getMessage(), "\n") ?: '';

        return trim((string) preg_replace('/\s+in (?:file )?anonymous-file-\d+\.less/', '', $message));
    }
}
