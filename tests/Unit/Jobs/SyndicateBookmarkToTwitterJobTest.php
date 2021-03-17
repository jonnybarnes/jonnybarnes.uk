<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SyndicateBookmarkToTwitter;
use App\Models\Bookmark;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SyndicateBookmarkToTwitterJobTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function weSendBookmarksToTwitter(): void
    {
        $json = json_encode([
            'url' => 'https://twitter.com/123'
        ]);
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], $json),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $bookmark = Bookmark::find(1);
        $job = new SyndicateBookmarkToTwitter($bookmark);
        $job->handle($client);

        $this->assertDatabaseHas('bookmarks', [
            'id' => 1,
            'syndicates' => '{"twitter": "https://twitter.com/123"}',
        ]);
    }
}
