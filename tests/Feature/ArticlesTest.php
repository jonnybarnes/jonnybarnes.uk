<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jonnybarnes\IndieWeb\Numbers;
use Tests\TestCase;

class ArticlesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function articlesPageLoads(): void
    {
        $response = $this->get('/blog');
        $response->assertViewIs('articles.index');
    }

    /** @test */
    public function singleArticlePageLoads()
    {
        $article = Article::factory()->create();
        $response = $this->get($article->link);
        $response->assertViewIs('articles.show');
    }

    /** @test */
    public function wrongDateInUrlRedirectsToCorrectDate()
    {
        $article = Article::factory()->create();
        $response = $this->get('/blog/1900/01/' . $article->titleurl);
        $response->assertRedirect('/blog/' . date('Y') . '/' . date('m') . '/' . $article->titleurl);
    }

    /** @test */
    public function oldUrlsWithIdAreRedirected()
    {
        $article = Article::factory()->create();
        $num60Id = resolve(Numbers::class)->numto60($article->id);
        $response = $this->get('/blog/s/' . $num60Id);
        $response->assertRedirect($article->link);
    }

    /** @test  */
    public function unknownSlugGetsNotFoundResponse()
    {
        $response = $this->get('/blog/' . date('Y') . '/' . date('m') . '/unknown-slug');
        $response->assertNotFound();
    }

    /** @test */
    public function unknownArticleIdGetsNotFoundResponse()
    {
        $response = $this->get('/blog/s/22');
        $response->assertNotFound();
    }
}
