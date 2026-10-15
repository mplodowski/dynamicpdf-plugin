<?php

use Renatio\DynamicPDF\Classes\TwigData;
use System\Models\File;

enum TwigDataStatus: string
{
    case Paid = 'paid';
}

enum TwigDataSize
{
    case Large;
}

describe('TwigData', function () {
    it('turns objects into plain values', function () {
        $props = new class
        {
            public string $name = 'Public';

            protected string $secret = 'Hidden';
        };
        $json = new class implements JsonSerializable
        {
            public function jsonSerialize(): object
            {
                return (object) ['total' => 10];
            }
        };
        $stringable = new class implements Stringable
        {
            public function __toString(): string
            {
                return 'text';
            }
        };

        expect((new TwigData)->convert([
            'props' => $props, 'json' => $json, 'stringable' => $stringable,
            'backed' => TwigDataStatus::Paid, 'unit' => TwigDataSize::Large,
        ]))->toBe([
            'props' => ['name' => 'Public'], 'json' => ['total' => 10], 'stringable' => 'text',
            'backed' => 'paid', 'unit' => 'Large',
        ]);
    });

    it('cuts cycles and deep nesting', function () {
        $cycle = new stdClass;
        $cycle->self = $cycle;
        $deep = $leaf = new stdClass;

        for ($i = 0; $i < 20; $i++) {
            $leaf = $leaf->next = new stdClass;
        }

        $data = (new TwigData)->convert(['cycle' => $cycle, 'deep' => $deep]);

        expect($data['cycle'])->toBe(['self' => null])
            ->and(json_encode($data['deep']))->toBe(str_repeat('{"next":', 11) . 'null' . str_repeat('}', 11));
    });

    it('adds the url to a file, which does not append it', function () {
        $file = new File(['disk_name' => 'abcdef0123456789.png', 'file_name' => 'logo.png']);

        expect((new TwigData)->convert(['file' => $file])['file'])->toMatchArray(['path' => $file->getPath(), 'url' => $file->getPath()]);
    });
});
