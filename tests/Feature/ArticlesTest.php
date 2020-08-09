<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArticlesTest extends TestCase
{
    public function test_articles_page()
    {
        $response = $this->get('/blog');
        $response->assertViewIs('articles.index');
    }

    public function test_single_article()
    {
        $response = $this->get('/blog/' . date('Y') . '/' . date('m') . '/some-code-i-did');
        $response->assertViewIs('articles.show');
    }

    public function test_wrong_date_redirects()
    {
        $response = $this->get('/blog/1900/01/some-code-i-did');
        $response->assertRedirect('/blog/' . date('Y') . '/' . date('m') . '/some-code-i-did');
    }

    public function test_redirect_for_id()
    {
        $response = $this->get('/blog/s/2');
        $response->assertRedirect('/blog/' . date('Y') . '/' . date('m') . '/some-code-i-did');
    }

    /** @test  */
    public function unknownSlugGives404()
    {
        $response = $this->get('/blog/' . date('Y') . '/' . date('m') . '/unknown-slug');
        $response->assertNotFound();
    }

    /** @test */
    public function unknownArticleIdGives404()
    {
        $response = $this->get('/blog/s/22');
        $response->assertNotFound();
    }
}
