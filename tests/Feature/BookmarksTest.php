<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\TestToken;
use App\Jobs\ProcessBookmark;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SyndicateBookmarkToTwitter;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BookmarksTest extends TestCase
{
    use DatabaseTransactions, TestToken;

    public function test_bookmarks_page()
    {
        $response = $this->get('/bookmarks');
        $response->assertViewIs('bookmarks.index');
    }

    public function test_single_bookmark_page()
    {
        $response = $this->get('/bookmarks/1');
        $response->assertViewIs('bookmarks.show');
    }

    public function test_browsershot_job_dispatches_when_bookmark_added_http_post_syntax()
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
        ])->post('/api/post', [
            'h' => 'entry',
            'bookmark-of' => 'https://example.org/blog-post',
            'mp-syndicate-to' => [
                'https://twitter.com/jonnybarnes',
            ],
        ]);

        $response->assertJson(['response' => 'created']);

        Queue::assertPushed(ProcessBookmark::class);
        Queue::assertPushed(SyndicateBookmarkToTwitter::class);
        $this->assertDatabaseHas('bookmarks', ['url' => 'https://example.org/blog-post']);
    }

    public function test_browsershot_job_dispatches_when_bookmark_added_json_syntax()
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
        ])->json('POST', '/api/post', [
            'type' => ['h-entry'],
            'properties' => [
                'bookmark-of' => ['https://example.org/blog-post'],
                'mp-syndicate-to' => [
                    'https://twitter.com/jonnybarnes',
                ],
            ],
        ]);

        $response->assertJson(['response' => 'created']);

        Queue::assertPushed(ProcessBookmark::class);
        Queue::assertPushed(SyndicateBookmarkToTwitter::class);
        $this->assertDatabaseHas('bookmarks', ['url' => 'https://example.org/blog-post']);
    }

    public function test_single_twitter_syndication_target_causes_job_dispatch_http_post_syntax()
    {
        Queue::fake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getToken(),
        ])->post('/api/post', [
            'h' => 'entry',
            'bookmark-of' => 'https://example.org/blog-post',
            'mp-syndicate-to' => 'https://twitter.com/jonnybarnes',
        ]);

        $response->assertJson(['response' => 'created']);

        Queue::assertPushed(ProcessBookmark::class);
        Queue::assertPushed(SyndicateBookmarkToTwitter::class);
        $this->assertDatabaseHas('bookmarks', ['url' => 'https://example.org/blog-post']);
    }

    public function test_tags_created_with_new_bookmark()
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
