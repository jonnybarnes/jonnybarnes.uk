<?php

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
        DB::table('articles')->insert([
            'titleurl' => 'my-new-blog',
            'title' => 'My New Blog',
            'main' => 'This is my new blog. It uses `Markdown`.',
            'published' => 1,
            'created_at' => '2016-01-12 15:51:01',
            'updated_at' => '2016-01-12 15:51:01',
        ]);
    }
}
