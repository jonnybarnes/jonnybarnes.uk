<?php

use App\Models\Note;
use Faker\Generator as Faker;
use Illuminate\Support\Carbon;

$factory->define(Note::class, function (Faker $faker) {
    $now = Carbon::now()->subDays(rand(5, 15));
    return [
        'note' => $faker->paragraph,
        'created_at' => $now,
        'updated_at' => $now,
    ];
});
