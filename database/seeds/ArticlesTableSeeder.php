<?php

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticlesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Article::create([
            'title' => 'My New Blog',
            'main' => 'This is *my* new blog. It uses `Markdown`.',
            'published' => 1,
        ]);
    }
}
