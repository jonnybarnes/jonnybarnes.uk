<?php

namespace Database\Seeders;

use App\Models\Bookmark;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class BookmarksTableSeeder extends Seeder
{
    /**
     * Seed the bookmarks table.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function run(): void
    {
        Bookmark::factory(10)
            ->has(Tag::factory()->count(1))
            ->create();
    }
}
