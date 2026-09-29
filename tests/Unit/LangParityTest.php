<?php

use Illuminate\Support\Arr;

it('gives every locale exactly the keys of the English file', function (string $file) {
    $expected = array_keys(Arr::dot(require __DIR__ . '/../../lang/en/lang.php'));
    $actual = array_keys(Arr::dot(require $file));

    expect(array_values(array_diff($expected, $actual)))->toBe([], 'missing')
        ->and(array_values(array_diff($actual, $expected)))->toBe([], 'extra');
})->with(function () {
    $files = [];

    foreach (glob(__DIR__ . '/../../lang/*/lang.php') ?: [] as $file) {
        $files[basename(dirname($file))] = $file;
    }

    return $files;
});
