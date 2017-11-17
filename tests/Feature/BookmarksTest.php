<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\TestToken;
use App\Jobs\ProcessBookmark;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BookmarksTest extends TestCase
{
    use DatabaseTransactions, TestToken;

    public function test_browsershot_job_dispatches_when_bookmark_added()
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

    public function test_screenshot_of_google()
    {
        $url = 'https://www.google.co.uk';

        $uuid = (new \App\Services\BookmarkService())->saveScreenshot($url);

        $this->assertTrue(file_exists(public_path() . '/assets/img/bookmarks/' . $uuid . '.png'));
    }
}
