<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\User;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticlesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_articles_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/admin/blog');
        $response->assertSeeText('Select article to edit:');
    }

    #[Test]
    public function admin_can_load_form_to_create_article(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/admin/blog/create');
        $response->assertSeeText('Title (URL)');
    }

    #[Test]
    public function admi_n_can_create_new_article(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)
            ->post('/admin/blog', [
                'title' => 'Test Title',
                'main' => 'Article content',
            ]);
        $this->assertDatabaseHas('articles', ['title' => 'Test Title']);
    }

    #[Test]
    public function admin_can_create_new_article_with_file(): void
    {
        $user = User::factory()->make();
        $faker = Factory::create();
        $text = $faker->text;
        if ($fh = fopen(sys_get_temp_dir().'/article.md', 'w')) {
            fwrite($fh, $text);
            fclose($fh);
        }
        $path = sys_get_temp_dir().'/article.md';
        $file = new UploadedFile($path, 'article.md', 'text/plain', null, true);

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

    #[Test]
    public function article_can_load_form_to_edit_article(): void
    {
        $user = User::factory()->make();
        $article = Article::factory()->create([
            'main' => 'This is *my* new blog. It uses `Markdown`.',
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/blog/'.$article->id.'/edit');
        $response->assertSeeText('This is *my* new blog. It uses `Markdown`.');
    }

    #[Test]
    public function admin_can_edit_article(): void
    {
        $user = User::factory()->make();
        $article = Article::factory()->create();

        $this->actingAs($user)
            ->post('/admin/blog/'.$article->id, [
                '_method' => 'PUT',
                'title' => 'My New Blog',
                'main' => 'This article has been edited',
            ]);
        $this->assertDatabaseHas('articles', [
            'title' => 'My New Blog',
            'main' => 'This article has been edited',
        ]);
    }

    #[Test]
    public function admin_can_delete_article(): void
    {
        $user = User::factory()->make();
        $article = Article::factory()->create();

        $this->actingAs($user)
            ->post('/admin/blog/'.$article->id, [
                '_method' => 'DELETE',
            ]);
        $this->assertSoftDeleted('articles', [
            'title' => $article->title,
        ]);
    }
}
