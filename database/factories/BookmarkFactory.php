<?php

use App\Models\Bookmark;
use Faker\Generator as Faker;

$factory->define(Bookmark::class, function (Faker $faker) {
    return [
        'url' => $faker->url,
        'name' => $faker->sentence,
        'content' => $faker->text,
    ];
});
