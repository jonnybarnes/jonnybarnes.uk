<?php

use Faker\Generator as Faker;

$factory->define(App\Note::class, function (Faker $faker) {
    return [
        'note' => $faker->paragraph,
        'tweet_id' => $faker->randomNumber(9),
        'facebook_url' => 'https://facebook.com/' . $faker->randomNumber(9),
    ];
});
