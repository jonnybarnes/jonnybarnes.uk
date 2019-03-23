<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArticlesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_page()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
                         ->get('/admin/blog');
        $response->assertSeeText('Select article to edit:');
    }

    public function test_create_page()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
                         ->get('/admin/blog/create');
        $response->assertSeeText('Title (URL)');
    }

    public function test_create_new_article()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user)
             ->post('/admin/blog', [
                 'title' => 'Test Title',
                 'main' => 'Article content'
             ]);
        $this->assertDatabaseHas('articles', ['title' => 'Test Title']);
    }

    public function test_create_new_article_with_upload()
    {
        $user = factory(User::class)->create();
        $faker = \Faker\Factory::create();
        $text = $faker->text;
        if ($fh = fopen(sys_get_temp_dir() . '/article.md', 'w')) {
            fwrite($fh, $text);
            fclose($fh);
        }
        $path = sys_get_temp_dir() . '/article.md';
        $file = new UploadedFile($path, 'article.md', 'text/plain', filesize($path), null, true);

        $this->actingAs($user)
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
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
                         ->get('/admin/blog/1/edit');
        $response->assertSeeText('This is *my* new blog. It uses `Markdown`.');
    }

    public function test_edit_article()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user)
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
        $user = factory(User::class)->create();

        $this->actingAs($user)
             ->post('/admin/blog/1', [
                 '_method' => 'DELETE',
             ]);
        $this->assertSoftDeleted('articles', [
            'title' => 'My New Blog',
        ]);
    }
}
