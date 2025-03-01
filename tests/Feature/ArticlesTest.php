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
    public function articlesPageLoads(): void
    {
        $response = $this->get('/blog');
        $response->assertViewIs('articles.index');
    }

    #[Test]
    public function singleArticlePageLoads()
    {
        $article = Article::factory()->create();
        $response = $this->get($article->link);
        $response->assertViewIs('articles.show');
    }

    #[Test]
    public function wrongDateInUrlRedirectsToCorrectDate()
    {
        $article = Article::factory()->create();
        $response = $this->get('/blog/1900/01/' . $article->titleurl);
        $response->assertRedirect('/blog/' . date('Y') . '/' . date('m') . '/' . $article->titleurl);
    }

    #[Test]
    public function oldUrlsWithIdAreRedirected()
    {
        $article = Article::factory()->create();
        $num60Id = resolve(Numbers::class)->numto60($article->id);
        $response = $this->get('/blog/s/' . $num60Id);
        $response->assertRedirect($article->link);
    }

    #[Test]
    public function unknownSlugGetsNotFoundResponse()
    {
        $response = $this->get('/blog/' . date('Y') . '/' . date('m') . '/unknown-slug');
        $response->assertNotFound();
    }

    #[Test]
    public function unknownArticleIdGetsNotFoundResponse()
    {
        $response = $this->get('/blog/s/22');
        $response->assertNotFound();
    }

    #[Test]
    public function someUrlsDoNotParseCorrectly(): void
    {
        $response = $this->get('/blog/feed.js');
        $response->assertNotFound();
    }
}
