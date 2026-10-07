<?php

namespace Renatio\DynamicPDF\Classes;

use Exception;
use Less_Parser;
use Less_Tree;
use Less_Tree_Call;
use Less_Tree_Import;
use October\Rain\Exception\ApplicationException;

/**
 * less.php resolves imports and file functions before dompdf's chroot applies, so the parsed
 * tree is checked for them first.
 */
class LessCompiler
{
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
                throw new ApplicationException(trans('renatio.dynamicpdf::lang.layout.css_reads_files'));
            }

            return $parser->getCss();
        } catch (ApplicationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ApplicationException(trans('renatio.dynamicpdf::lang.layout.css_invalid') . ' ' . $this->describe($e));
        }
    }

    /**
     * Walks every public property rather than each node's accept(), which skips imports nested in
     * mixins, media blocks and detached rulesets.
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

    protected function describe(Exception $e): string
    {
        $message = strtok($e->getMessage(), "\n") ?: '';

        return trim((string) preg_replace('/\s+in (?:file )?anonymous-file-\d+\.less/', '', $message));
    }
}
