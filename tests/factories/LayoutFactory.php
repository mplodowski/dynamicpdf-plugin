<?php

use Faker\Generator as Faker;
use Renatio\DynamicPDF\Models\Layout;

$factory->define(Layout::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence,
        'code' => $faker->word,
        'content_html' => File::get(__DIR__.'/../fixtures/layout.htm'),
        'content_css' => File::get(__DIR__.'/../fixtures/style.css'),
        'is_locked' => 1,
    ];
});