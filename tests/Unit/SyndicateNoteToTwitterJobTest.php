<?php

namespace Tests\Unit;

use App\Note;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use App\Jobs\SyndicateNoteToTwitter;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SyndicateNoteToTwitterJobTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_job()
    {
        $json = json_encode([
            'url' => 'https://twitter.com/i/web/status/123'
        ]);
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], $json),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::find(1);
        $job = new SyndicateNoteToTwitter($note);
        $job->handle($client);

        $this->assertDatabaseHas('notes', [
            'id' => 1,
            'tweet_id' => '123',
        ]);
    }
}
