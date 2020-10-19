<?php

namespace Database\Seeders;

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
        Bookmark::factory(10)
            ->has(Tag::factory()->count(1))
            ->create();
    }
}
