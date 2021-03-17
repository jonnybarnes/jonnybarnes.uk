<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ArticlesTest extends TestCase
{
    /** @test */
    public function articlesPageLoads(): void
    {
        $response = $this->get('/blog');
        $response->assertViewIs('articles.index');
    }

    /** @test */
    public function singleArticlePageLoads()
    {
        $response = $this->get('/blog/' . date('Y') . '/' . date('m') . '/some-code-i-did');
        $response->assertViewIs('articles.show');
    }

    /** @test */
    public function wrongDateInUrlRedirectsToCorrectDate()
    {
        $response = $this->get('/blog/1900/01/some-code-i-did');
        $response->assertRedirect('/blog/' . date('Y') . '/' . date('m') . '/some-code-i-did');
    }

    /** @test */
    public function oldUrlsWithIdAreRedirected()
    {
        $response = $this->get('/blog/s/2');
        $response->assertRedirect('/blog/' . date('Y') . '/' . date('m') . '/some-code-i-did');
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
