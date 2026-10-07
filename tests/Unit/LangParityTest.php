<?php

use Illuminate\Support\Arr;

dataset('locales', function () {
    $files = [];

    foreach (glob(__DIR__ . '/../../lang/*/lang.php') ?: [] as $file) {
        $files[basename(dirname($file))] = $file;
    }

    return $files;
});

it('gives every locale exactly the keys of the English file', function (string $file) {
    $expected = array_keys(Arr::dot(require __DIR__ . '/../../lang/en/lang.php'));
    $actual = array_keys(Arr::dot(require $file));

    expect(array_values(array_diff($expected, $actual)))->toBe([], 'missing')
        ->and(array_values(array_diff($actual, $expected)))->toBe([], 'extra');
})->with('locales');

it('keeps the placeholders and the Markdown of the English texts', function (string $file) {
    $translations = Arr::dot(require $file);

    $signature = function (string $text): array {
        preg_match_all('/:([a-z_]+)/', $text, $matches);
        $placeholders = array_unique($matches[1]);
        sort($placeholders);

        return [
            'placeholders' => $placeholders,
            'bold' => substr_count($text, '**'),
            'code' => substr_count($text, '`'),
        ];
    };

    foreach (Arr::dot(require __DIR__ . '/../../lang/en/lang.php') as $key => $english) {
        expect($signature($translations[$key] ?? ''))->toBe($signature($english), $key);
    }
})->with('locales');
