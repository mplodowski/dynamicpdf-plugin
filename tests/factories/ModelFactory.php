<?php

$factory->define('Renatio\DynamicPDF\Models\Template', function (Faker\Generator $faker) {
    return [
        'title' => $faker->sentence,
        'code' => $faker->word,
        'content_html' => $faker->paragraph(5),
    ];
});

$factory->define('Renatio\DynamicPDF\Models\Layout', function (Faker\Generator $faker) {
    return [
        'name' => $faker->sentence,
        'code' => $faker->word,
        'content_html' => $faker->paragraph(5),
    ];
});