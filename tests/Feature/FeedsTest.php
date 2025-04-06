<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Note;
use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeedsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the blog RSS feed.
     */
    #[Test]
    public function blog_rss_feed_is_present(): void
    {
        Article::factory()->count(3)->create();
        $response = $this->get('/blog/feed.rss');
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
        $response->assertOk();
    }

    /**
     * Test the notes RSS feed.
     */
    #[Test]
    public function notes_rss_feed_is_present(): void
    {
        Note::factory()->count(3)->create();
        $response = $this->get('/notes/feed.rss');
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
        $response->assertOk();
    }

    /**
     * Test the blog RSS feed.
     */
    #[Test]
    public function blog_atom_feed_is_present(): void
    {
        Article::factory()->count(3)->create();
        $response = $this->get('/blog/feed.atom');
        $response->assertHeader('Content-Type', 'application/atom+xml; charset=utf-8');
        $response->assertOk();
    }

    #[Test]
    public function blog_jf2_feed_is_present(): void
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
                'name' => config('user.display_name'),
                'url' => config('app.url'),
            ],
            'children' => [[
                'type' => 'entry',
                'post-type' => 'article',
            ]],
        ]);
    }

    /**
     * Test the notes RSS feed.
     */
    #[Test]
    public function notes_atom_feed_is_present(): void
    {
        Note::factory()->count(3)->create();
        $response = $this->get('/notes/feed.atom');
        $response->assertHeader('Content-Type', 'application/atom+xml; charset=utf-8');
        $response->assertOk();
    }

    /**
     * Test the blog JSON feed.
     */
    #[Test]
    public function blog_json_feed_is_present(): void
    {
        Article::factory()->count(3)->create();
        $response = $this->get('/blog/feed.json');
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertOk();
    }

    /**
     * Test the notes JSON feed.
     */
    #[Test]
    public function notes_json_feed_is_present(): void
    {
        Note::factory()->count(3)->create();
        $response = $this->get('/notes/feed.json');
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertOk();
    }

    #[Test]
    public function notes_jf2_feed_is_present(): void
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
                'name' => config('user.display_name'),
                'url' => config('app.url'),
            ],
            'children' => [[
                'type' => 'entry',
                'post-type' => 'note',
            ]],
        ]);
    }

    /**
     * Each JSON feed item must have one of `content_text` or `content_html`,
     * and whichever one they have can’t be `null`.
     */
    #[Test]
    public function json_feeds_have_required_attributes(): void
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

    #[Test]
    public function json_note_feed_loads_place_data_without_lazy_loading(): void
    {
        $place = Place::factory()->create();
        Note::factory()->create(['note' => null, 'place_id' => $place->id]);
        $response = $this->get('/notes/feed.json');

        $response->assertOk();
    }
}
