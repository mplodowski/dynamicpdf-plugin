<?php

namespace Renatio\DynamicPDF\Classes;

use SplFixedArray;

class TextDiff
{
    public const CONTEXT = 'context';

    public const ADDED = 'added';

    public const REMOVED = 'removed';

    /**
     * Above this many cells the LCS table costs too much memory, so the changed block is shown
     * as removed and added whole.
     */
    public const MAX_CELLS = 1_000_000;

    public function __construct(protected int $contextLines = 3, protected int $maxCells = self::MAX_CELLS)
    {
    }

    /**
     * @return list<list<array{type: string, old: ?int, new: ?int, text: string}>>
     */
    public function hunks(string $old, string $new): array
    {
        return $this->group($this->lines($this->split($old), $this->split($new)));
    }

    /**
     * @return list<string>
     */
    protected function split(string $text): array
    {
        $text = str_replace("\r\n", "\n", $text);

        return $text === '' ? [] : explode("\n", $text);
    }

    /**
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<array{type: string, old: ?int, new: ?int, text: string}>
     */
    protected function lines(array $old, array $new): array
    {
        $oldCount = count($old);
        $newCount = count($new);
        $prefix = 0;

        while ($prefix < $oldCount && $prefix < $newCount && $old[$prefix] === $new[$prefix]) {
            $prefix++;
        }

        $suffix = 0;

        while ($suffix < $oldCount - $prefix && $suffix < $newCount - $prefix && $old[$oldCount - $suffix - 1] === $new[$newCount - $suffix - 1]) {
            $suffix++;
        }

        $lines = [];

        for ($i = 0; $i < $prefix; $i++) {
            $lines[] = ['type' => self::CONTEXT, 'old' => $i + 1, 'new' => $i + 1, 'text' => $old[$i]];
        }

        $oldMiddle = array_slice($old, $prefix, $oldCount - $prefix - $suffix);
        $newMiddle = array_slice($new, $prefix, $newCount - $prefix - $suffix);

        foreach ($this->middle($oldMiddle, $newMiddle) as [$type, $oldIndex, $newIndex]) {
            $lines[] = [
                'type' => $type,
                'old' => $type === self::ADDED ? null : $prefix + $oldIndex + 1,
                'new' => $type === self::REMOVED ? null : $prefix + $newIndex + 1,
                'text' => $type === self::ADDED ? $newMiddle[$newIndex] : $oldMiddle[$oldIndex],
            ];
        }

        for ($i = $suffix; $i > 0; $i--) {
            $lines[] = ['type' => self::CONTEXT, 'old' => $oldCount - $i + 1, 'new' => $newCount - $i + 1, 'text' => $old[$oldCount - $i]];
        }

        return $lines;
    }

    /**
     * Each run of changes lists its removed lines before its added ones.
     *
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<array{string, int, int}>
     */
    protected function middle(array $old, array $new): array
    {
        $operations = [];
        $removed = [];
        $added = [];

        foreach ($this->operations($old, $new) as $operation) {
            if ($operation[0] === self::REMOVED) {
                $removed[] = $operation;
            } elseif ($operation[0] === self::ADDED) {
                $added[] = $operation;
            } else {
                array_push($operations, ...$removed, ...$added);
                $operations[] = $operation;
                $removed = $added = [];
            }
        }

        return array_merge($operations, $removed, $added);
    }

    /**
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<array{string, int, int}>
     */
    protected function operations(array $old, array $new): array
    {
        $oldCount = count($old);
        $newCount = count($new);
        $width = $newCount + 1;

        if (($oldCount + 1) * $width > $this->maxCells) {
            return array_merge(
                array_map(fn (int $i) => [self::REMOVED, $i, 0], array_keys($old)),
                array_map(fn (int $j) => [self::ADDED, 0, $j], array_keys($new)),
            );
        }

        $lengths = new SplFixedArray(($oldCount + 1) * $width);

        for ($i = $oldCount; $i >= 0; $i--) {
            for ($j = $newCount; $j >= 0; $j--) {
                $lengths[$i * $width + $j] = match (true) {
                    $i === $oldCount || $j === $newCount => 0,
                    $old[$i] === $new[$j] => $lengths[($i + 1) * $width + $j + 1] + 1,
                    default => max($lengths[($i + 1) * $width + $j], $lengths[$i * $width + $j + 1]),
                };
            }
        }

        $operations = [];
        $i = $j = 0;

        while ($i < $oldCount || $j < $newCount) {
            if ($i < $oldCount && $j < $newCount && $old[$i] === $new[$j]) {
                $operations[] = [self::CONTEXT, $i++, $j++];
            } elseif ($j === $newCount || ($i < $oldCount && $lengths[($i + 1) * $width + $j] >= $lengths[$i * $width + $j + 1])) {
                $operations[] = [self::REMOVED, $i++, $j];
            } else {
                $operations[] = [self::ADDED, $i, $j++];
            }
        }

        return $operations;
    }

    /**
     * @param  list<array{type: string, old: ?int, new: ?int, text: string}>  $lines
     * @return list<list<array{type: string, old: ?int, new: ?int, text: string}>>
     */
    protected function group(array $lines): array
    {
        $changed = array_keys(array_filter($lines, fn (array $line) => $line['type'] !== self::CONTEXT));

        $hunks = [];
        $start = $end = null;

        foreach ($changed as $index) {
            if ($end !== null && $index - $this->contextLines > $end + 1) {
                $hunks[] = array_slice($lines, $start, $end - $start + 1);
                $start = null;
            }

            $start ??= max(0, $index - $this->contextLines);
            $end = min(count($lines) - 1, $index + $this->contextLines);
        }

        if ($start !== null) {
            $hunks[] = array_slice($lines, $start, $end - $start + 1);
        }

        return $hunks;
    }
}
