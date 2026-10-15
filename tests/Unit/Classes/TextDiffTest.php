<?php

use Renatio\DynamicPDF\Classes\TextDiff;

/**
 * @param  list<list<array{type: string, old: ?int, new: ?int, text: string}>>  $hunks
 * @return list<list<array{string, ?int, ?int, string}>>
 */
function textDiffRows(array $hunks): array
{
    return array_map(fn (array $hunk) => array_map(
        fn (array $line) => [$line['type'][0], $line['old'], $line['new'], $line['text']],
        $hunk,
    ), $hunks);
}

describe('TextDiff', function () {
    it('finds no hunks in equal texts, also when only the line endings differ', function () {
        expect((new TextDiff)->hunks("a\nb\nc", "a\nb\nc"))->toBe([])
            ->and((new TextDiff)->hunks("a\r\nb\r\nc", "a\nb\nc"))->toBe([]);
    });

    it('shows a changed line as removed then added within three lines of context', function () {
        $old = implode("\n", range(1, 9));
        $new = str_replace('5', 'five', $old);

        expect(textDiffRows((new TextDiff)->hunks($old, $new)))->toBe([[
            ['c', 2, 2, '2'],
            ['c', 3, 3, '3'],
            ['c', 4, 4, '4'],
            ['r', 5, null, '5'],
            ['a', null, 5, 'five'],
            ['c', 6, 6, '6'],
            ['c', 7, 7, '7'],
            ['c', 8, 8, '8'],
        ]]);
    });

    it('numbers lines inserted at the start and at the end', function () {
        expect(textDiffRows((new TextDiff)->hunks("a\nb", "new\na\nb\nlast")))->toBe([[
            ['a', null, 1, 'new'],
            ['c', 1, 2, 'a'],
            ['c', 2, 3, 'b'],
            ['a', null, 4, 'last'],
        ]]);
    });

    it('splits changes far apart into separate hunks and keeps near ones together', function () {
        $old = range(1, 30);
        $new = $old;
        $new[1] = 'x';
        $new[8] = 'y';
        $new[25] = 'z';

        $hunks = (new TextDiff)->hunks(implode("\n", $old), implode("\n", $new));

        expect($hunks)->toHaveCount(2)
            ->and($hunks[0][0]['old'])->toBe(1)
            ->and($hunks[0][count($hunks[0]) - 1]['old'])->toBe(12)
            ->and($hunks[1][0]['old'])->toBe(23)
            ->and($hunks[1][count($hunks[1]) - 1]['old'])->toBe(29);
    });

    it('shows the changed block as removed and added whole when it is too big to compare', function () {
        $hunks = (new TextDiff(3, 10))->hunks("head\na\nb\nc\ntail", "head\nb\nc\nd\ntail");

        expect(textDiffRows($hunks))->toBe([[
            ['c', 1, 1, 'head'],
            ['r', 2, null, 'a'],
            ['r', 3, null, 'b'],
            ['r', 4, null, 'c'],
            ['a', null, 2, 'b'],
            ['a', null, 3, 'c'],
            ['a', null, 4, 'd'],
            ['c', 5, 5, 'tail'],
        ]]);
    });
});
