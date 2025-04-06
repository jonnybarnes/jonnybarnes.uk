<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessBookmark;
use App\Models\Bookmark;
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
        $response = $this->get('/bookmarks/' . $bookmark->id);
        $response->assertViewIs('bookmarks.show');
    }

    #[Test]
    public function when_bookmark_is_added_using_http_syntax_check_job_to_take_screenshot_is_invoked(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
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
            'Authorization' => 'Bearer ' . $this->getToken(),
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
    public function when_the_bookmark_is_created_check_necessary_tags_are_also_created(): void
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
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
