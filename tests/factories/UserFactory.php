<?php

use Backend\Models\User;
use Faker\Generator as Faker;

$factory->define(User::class, function (Faker $faker) {
    return [
        'email' => $faker->email,
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'login' => $faker->email,
        'password' => $password = $faker->password,
        'password_confirmation' => $password,
    ];
});