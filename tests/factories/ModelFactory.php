<?php

use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

$factory->define(Template::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->sentence,
        'code' => $faker->word,
        'content_html' => $faker->paragraph(5),
    ];
});

$factory->define(Layout::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->sentence,
        'code' => $faker->word,
        'content_html' => $faker->paragraph(5),
    ];
});