<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArticlesAdminTest extends TestCase
{
    use DatabaseTransactions;

    public function test_create_new_article()
    {
        $this->withSession(['loggedin' => true])
             ->post('/admin/blog', [
                 'title' => 'Test Title',
                 'main' => 'Article content'
             ]);
        $this->assertDatabaseHas('articles', ['title' => 'Test Title']);
    }

    public function test_create_new_article_with_upload()
    {
        $faker = \Faker\Factory::create();
        $text = $faker->text;
        if ($fh = fopen(sys_get_temp_dir() . '/article.md', 'w')) {
            fwrite($fh, $text);
            fclose($fh);
        }
        $path = sys_get_temp_dir() . '/article.md';
        $file = new UploadedFile($path, 'article.md', 'text/plain', filesize($path), null, true);

        $this->withSession(['loggedin' => true])
             ->post('/admin/blog', [
                'title' => 'Uploaded Article',
                'article' => $file,
             ]);

        $this->assertDatabaseHas('articles', [
            'title' => 'Uploaded Article',
            'main' => $text,
        ]);
    }
}
