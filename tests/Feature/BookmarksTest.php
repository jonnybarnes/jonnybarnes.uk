<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessBookmark;
use App\Models\Bookmark;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\TestToken;

class BookmarksTest extends TestCase
{
    use RefreshDatabase, TestToken;

    #[Test]
    public function bookmarks_page_loads_without_error(): void
    {
        $response = $this->get('/bookmarks');
        $response->assertViewIs('bookmarks.index');
    }

    #[Test]
    public function single_bookmark_page_loads_without_error(): void
    {
        $bookmark = Bookmark::factory()->create();
        $response = $this->get('/bookmarks/'.$bookmark->id);
        $response->assertViewIs('bookmarks.show');
    }

    #[Test]
    public function non_numeric_bookmark_id_gives_not_found_response(): void
    {
        $response = $this->get('/bookmarks/twcamp^share|twsrc^ios');
        $response->assertNotFound();
    }

    #[Test]
    public function when_bookmark_is_added_using_http_syntax_check_job_to_take_screenshot_is_invoked(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->getToken(),
        ])->post('/api/post', [
            'h' => 'entry',
            'bookmark-of' => 'https://example.org/blog-post',
        ]);

        $response->assertJson(['response' => 'created']);

        Queue::assertPushed(ProcessBookmark::class);
        $this->assertDatabaseHas('bookmarks', ['url' => 'https://example.org/blog-post']);
    }

    #[Test]
    public function when_bookmark_is_added_using_json_syntax_check_job_to_take_screenshot_is_invoked(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->getToken(),
        ])->json('POST', '/api/post', [
            'type' => ['h-entry'],
            'properties' => [
                'bookmark-of' => ['https://example.org/blog-post'],
            ],
        ]);

        $response->assertJson(['response' => 'created']);

        Queue::assertPushed(ProcessBookmark::class);
        $this->assertDatabaseHas('bookmarks', ['url' => 'https://example.org/blog-post']);
    }

    #[Test]
    public function tagged_bookmarks_page_only_shows_bookmarks_with_that_tag(): void
    {
        $tag = Tag::factory()->create(['tag' => 'php']);
        $tagged = Bookmark::factory()->create();
        $tagged->tags()->attach($tag);
        $untagged = Bookmark::factory()->create();

        $response = $this->get('/bookmarks/tagged/php');
        $response->assertViewIs('bookmarks.tagged');
        $response->assertSee($tagged->url);
        $response->assertDontSee($untagged->url);
    }

    #[Test]
    public function bookmark_local_uri_attribute_returns_correct_url(): void
    {
        $bookmark = Bookmark::factory()->create();
        $this->assertEquals(config('app.url').'/bookmarks/'.$bookmark->id, $bookmark->local_uri);
    }

    #[Test]
    public function when_the_bookmark_is_created_check_necessary_tags_are_also_created(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->getToken(),
        ])->json('POST', '/api/post', [
            'type' => ['h-entry'],
            'properties' => [
                'bookmark-of' => ['https://example.org/blog-post'],
                'category' => ['tag1', 'tag2'],
            ],
        ]);

        $response->assertJson(['response' => 'created']);

        Queue::assertPushed(ProcessBookmark::class);
        $this->assertDatabaseHas('bookmarks', ['url' => 'https://example.org/blog-post']);
    }
}
