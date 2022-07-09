<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SyndicateBookmarkToTwitter;
use App\Models\Bookmark;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyndicateBookmarkToTwitterJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function weSendBookmarksToTwitter(): void
    {
        $faker = \Faker\Factory::create();
        $randomNumber = $faker->randomNumber();
        $json = json_encode([
            'url' => 'https://twitter.com/' . $randomNumber,
        ]);
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], $json),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $bookmark = Bookmark::factory()->create();
        $job = new SyndicateBookmarkToTwitter($bookmark);
        $job->handle($client);

        $this->assertDatabaseHas('bookmarks', [
            'syndicates' => '{"twitter": "https://twitter.com/' . $randomNumber . '"}',
        ]);
    }
}
