<?php

use Illuminate\Database\Seeder;

class BookmarksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Bookmark::class, 10)->create()->each(function ($bookmark) {
            $bookmark->tag()->save(factory(App\Tag::class)->make());
        });
    }
}
