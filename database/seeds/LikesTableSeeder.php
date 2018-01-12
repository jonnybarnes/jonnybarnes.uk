<?php

use App\Models\Like;
use Faker\Generator;
use Illuminate\Database\Seeder;

class LikesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Like::class, 10)->create();
        sleep(1);

        $faker = new Generator();
        $faker->addProvider(new \Faker\Provider\en_US\Person($faker));
        $faker->addProvider(new \Faker\Provider\Lorem($faker));
        $faker->addProvider(new \Faker\Provider\Internet($faker));
        Like::create([
            'url' => $faker->url,
            'author_url' => $faker->url,
            'author_name' => $faker->name,
        ]);
        sleep(1);

        Like::create(['url' => 'https://example.com']);
    }
}
