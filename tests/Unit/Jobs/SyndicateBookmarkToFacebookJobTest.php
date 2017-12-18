<?php

namespace Tests\Unit\Jobs;

use App\Bookmark;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use App\Jobs\SyndicateBookmarkToFacebook;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SyndicateBookmarkToFacebookJobTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_job()
    {
        $json = json_encode([
            'url' => 'https://facebook.com/123'
        ]);
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], $json),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $bookmark = Bookmark::find(1);
        $job = new SyndicateBookmarkToFacebook($bookmark);
        $job->handle($client);

        $this->assertDatabaseHas('bookmarks', [
            'id' => 1,
            'syndicates' => '{"facebook": "https://facebook.com/123"}',
        ]);
    }
}
