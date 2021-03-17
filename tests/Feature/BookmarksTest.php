<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessBookmark;
use App\Jobs\SyndicateBookmarkToTwitter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\TestToken;

class BookmarksTest extends TestCase
{
    use DatabaseTransactions, TestToken;

    /** @test */
    public function bookmarksPageLoadsWithoutError(): void
    {
        $response = $this->get('/bookmarks');
        $response->assertViewIs('bookmarks.index');
    }

    /** @test */
    public function singleBookmarkPageLoadsWithoutError(): void
    {
        $response = $this->get('/bookmarks/1');
        $response->assertViewIs('bookmarks.show');
    }

    /** @test */
    public function whenBookmarkIsAddedUsingHttpSyntaxCheckJobToTakeScreenshotIsInvoked(): void
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

    /** @test */
    public function whenBookmarkIsAddedUsingJsonSyntaxCheckJobToTakeScreenshotIsInvoked(): void
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

    /** @test */
    public function whenTheBookmarkIsMarkedForPostingToTwitterCheckWeInvokeTheCorrectJob(): void
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

    /** @test */
    public function whenTheBookmarkIsCreatedCheckNecessaryTagsAreAlsoCreated(): void
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
