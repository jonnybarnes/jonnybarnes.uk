<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jonnybarnes\IndieWeb\Numbers;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticlesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function articles_page_loads(): void
    {
        $response = $this->get('/blog');
        $response->assertViewIs('articles.index');
    }

    #[Test]
    public function single_article_page_loads()
    {
        $article = Article::factory()->create();
        $response = $this->get($article->link);
        $response->assertViewIs('articles.show');
    }

    #[Test]
    public function articles_page_does_not_list_drafts(): void
    {
        Article::factory()->create(['title' => 'Published Article']);
        Article::factory()->create(['title' => 'Draft Article', 'published' => 0]);

        $this->get('/blog')
            ->assertSee('Published Article')
            ->assertDontSee('Draft Article');
    }

    #[Test]
    public function draft_article_is_viewable_and_marked_as_draft(): void
    {
        $article = Article::factory()->create(['published' => 0]);

        $this->get($article->link)
            ->assertOk()
            ->assertSee('draft-notice', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<title>[Draft] ', false);
    }

    #[Test]
    public function published_article_is_not_marked_as_draft(): void
    {
        $article = Article::factory()->create();

        $this->get($article->link)
            ->assertDontSee('draft-notice', false)
            ->assertDontSee('noindex', false);
    }

    #[Test]
    public function wrong_date_in_url_redirects_to_correct_date()
    {
        $article = Article::factory()->create();
        $response = $this->get('/blog/1900/01/'.$article->titleurl);
        $response->assertRedirect('/blog/'.date('Y').'/'.date('m').'/'.$article->titleurl);
    }

    #[Test]
    public function old_urls_with_id_are_redirected()
    {
        $article = Article::factory()->create();
        $num60Id = resolve(Numbers::class)->numto60($article->id);
        $response = $this->get('/blog/s/'.$num60Id);
        $response->assertRedirect($article->link);
    }

    #[Test]
    public function unknown_slug_gets_not_found_response()
    {
        $response = $this->get('/blog/'.date('Y').'/'.date('m').'/unknown-slug');
        $response->assertNotFound();
    }

    #[Test]
    public function unknown_article_id_gets_not_found_response()
    {
        $response = $this->get('/blog/s/22');
        $response->assertNotFound();
    }

    #[Test]
    public function some_urls_do_not_parse_correctly(): void
    {
        $response = $this->get('/blog/feed.js');
        $response->assertNotFound();
    }
}
