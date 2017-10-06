<?php

use Faker\Generator as Faker;

$factory->define(App\Like::class, function (Faker $faker) {
    return [
        'url' => $faker->url,
        'author_name' => $faker->name,
        'author_url' => $faker->url,
        'content' => '<html><body><div class="h-entry"><div class="e-content">' . $faker->realtext() . '</div></div></body></html>',
    ];
});
