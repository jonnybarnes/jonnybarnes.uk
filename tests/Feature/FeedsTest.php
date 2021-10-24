<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the blog RSS feed.
     *
     * @test
     */
    public function blogRssFeedIsPresent(): void
    {
        Article::factory()->count(3)->create();
        $response = $this->get('/blog/feed.rss');
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
        $response->assertOk();
    }

    /**
     * Test the notes RSS feed.
     *
     * @test
     */
    public function notesRssFeedIsPresent(): void
    {
        Note::factory()->count(3)->create();
        $response = $this->get('/notes/feed.rss');
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
        $response->assertOk();
    }

    /**
     * Test the blog RSS feed.
     *
     * @test
     */
    public function blogAtomFeedIsPresent(): void
    {
        Article::factory()->count(3)->create();
        $response = $this->get('/blog/feed.atom');
        $response->assertHeader('Content-Type', 'application/atom+xml; charset=utf-8');
        $response->assertOk();
    }

    /** @test */
    public function blogJf2FeedIsPresent(): void
    {
        Article::factory()->count(3)->create();
        $response = $this->get('/blog/feed.jf2');
        $response->assertHeader('Content-Type', 'application/jf2feed+json');
        $response->assertJson([
            'type' => 'feed',
            'name' => 'Blog feed for ' . config('app.name'),
            'url' => url('/blog'),
            'author' => [
                'type' => 'card',
                'name' => config('user.displayname'),
                'url' => config('app.longurl'),
            ],
            'children' => [[
                'type' => 'entry',
                'post-type' => 'article',
            ]]
        ]);
    }

    /**
     * Test the notes RSS feed.
     *
     * @test
     */
    public function notesAtomFeedIsPresent(): void
    {
        Note::factory()->count(3)->create();
        $response = $this->get('/notes/feed.atom');
        $response->assertHeader('Content-Type', 'application/atom+xml; charset=utf-8');
        $response->assertOk();
    }

    /**
     * Test the blog JSON feed.
     *
     * @test
     */
    public function blogJsonFeedIsPresent(): void
    {
        Article::factory()->count(3)->create();
        $response = $this->get('/blog/feed.json');
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertOk();
    }

    /**
     * Test the notes JSON feed.
     *
     * @test
     */
    public function notesJsonFeedIsPresent(): void
    {
        Note::factory()->count(3)->create();
        $response = $this->get('/notes/feed.json');
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertOk();
    }

    /** @test */
    public function notesJf2FeedIsPresent(): void
    {
        Note::factory()->count(3)->create();
        $response = $this->get('/notes/feed.jf2');
        $response->assertHeader('Content-Type', 'application/jf2feed+json');
        $response->assertJson([
            'type' => 'feed',
            'name' => 'Notes feed for ' . config('app.name'),
            'url' => url('/notes'),
            'author' => [
                'type' => 'card',
                'name' => config('user.displayname'),
                'url' => config('app.longurl'),
            ],
            'children' => [[
                'type' => 'entry',
                'post-type' => 'note',
            ]]
        ]);
    }

    /**
     * Each JSON feed item must have one of `content_text` or `content_html`,
     * and whichever one they have can’t be `null`.
     *
     * @test
     */
    public function jsonFeedsHaveRequiredAttributes(): void
    {
        Note::factory()->count(3)->create();
        $response = $this->get('/notes/feed.json');
        $data = json_decode($response->content());
        foreach ($data->items as $item) {
            $this->assertTrue(
                property_exists($item, 'content_text') ||
                property_exists($item, 'content_html')
            );
            if (property_exists($item, 'content_text')) {
                $this->assertNotNull($item->content_text);
            }
            if (property_exists($item, 'content_html')) {
                $this->assertNotNull($item->content_html);
            }
        }
    }
}
