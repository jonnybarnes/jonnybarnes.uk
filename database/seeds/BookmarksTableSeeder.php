<?php

use App\Models\{Bookmark, Tag};
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
        factory(Bookmark::class, 10)->create()->each(function ($bookmark) {
            $bookmark->tags()->save(factory(Tag::class)->make());
        });
    }
}
