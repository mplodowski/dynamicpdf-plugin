<?php

use Backend\Models\User;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

/*
 * User
 */
$factory->define(User::class, function ($faker) {
    return [
        'email' => $faker->email,
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'login' => $faker->email,
        'password' => $password = $faker->password,
        'password_confirmation' => $password,
    ];
});

/*
 * Layout
 */
$factory->define(Layout::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->sentence,
        'code' => $faker->word,
        'content_html' => File::get(__DIR__ . '/../fixtures/layout.htm'),
        'content_css' => File::get(__DIR__ . '/../fixtures/style.css'),
    ];
});

/*
 * Template
 */
$factory->define(Template::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->sentence,
        'code' => $faker->word,
        'content_html' => File::get(__DIR__ . '/../fixtures/template.htm'),
        'layout' => factory(Layout::class)->create(),
    ];
});