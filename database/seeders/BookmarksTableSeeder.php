<?php

namespace Database\Seeders;

use Illuminate\Support\Carbon;
use App\Models\{Bookmark, Tag};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookmarksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Bookmark::factory(10)->create();
        factory(Bookmark::class, 10)->create()->each(function ($bookmark) {
            $bookmark->tags()->save(factory(Tag::class)->make());

            $now = Carbon::now()->subDays(rand(2, 12));
            DB::table('bookmarks')
                ->where('id', $bookmark->id)
                ->update([
                    'created_at' => $now->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ]);
        });
    }
}
