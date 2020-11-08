<?php

namespace Database\Seeders;

use App\Models\Like;
use Faker\Generator;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LikesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Like::factory(10)->create();

        $now = Carbon::now()->subDays(rand(3, 6));
        $faker = new Generator();
        $faker->addProvider(new \Faker\Provider\en_US\Person($faker));
        $faker->addProvider(new \Faker\Provider\Lorem($faker));
        $faker->addProvider(new \Faker\Provider\Internet($faker));
        $likeFromAuthor = Like::create([
            'url' => $faker->url,
            'author_url' => $faker->url,
            'author_name' => $faker->name,
        ]);
        DB::table('likes')
            ->where('id', $likeFromAuthor->id)
            ->update([
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

        $now = Carbon::now()->subHours(rand(3, 6));
        $likeJustUrl = Like::create(['url' => 'https://example.com']);
        DB::table('likes')
            ->where('id', $likeJustUrl->id)
            ->update([
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
    }
}
