<?php

use Faker\Generator as Faker;

$factory->define(App\Bookmark::class, function (Faker $faker) {
    return [
        'url' => $faker->url,
        'name' => $faker->sentence,
        'content' => $faker->text,
    ];
});
