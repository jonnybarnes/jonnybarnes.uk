<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArticlesAdminTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_page()
    {
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin/blog');
        $response->assertSeeText('Select article to edit:');
    }

    public function test_create_page()
    {
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin/blog/create');
        $response->assertSeeText('Title (URL)');
    }

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

    public function test_see_edit_form()
    {
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin/blog/1/edit');
        $response->assertSeeText('This is *my* new blog. It uses `Markdown`.');
    }

    public function test_edit_article()
    {
        $this->withSession(['loggedin' => true])
             ->post('/admin/blog/1', [
                 '_method' => 'PUT',
                 'title' => 'My New Blog',
                 'main' => 'This article has been edited',
             ]);
        $this->assertDatabaseHas('articles', [
            'title' => 'My New Blog',
            'main' => 'This article has been edited',
        ]);
    }

    public function test_delete_article()
    {
        $this->withSession(['loggedin' => true])
             ->post('/admin/blog/1', [
                 '_method' => 'DELETE',
             ]);
        $this->assertSoftDeleted('articles', [
            'title' => 'My New Blog',
        ]);
    }
}
