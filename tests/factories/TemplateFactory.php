<?php

use Faker\Generator as Faker;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

$factory->define(Template::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'code' => $faker->word,
        'content_html' => File::get(__DIR__.'/../fixtures/template.htm'),
        'layout' => factory(Layout::class)->create(),
        'is_custom' => 1,
    ];
});
