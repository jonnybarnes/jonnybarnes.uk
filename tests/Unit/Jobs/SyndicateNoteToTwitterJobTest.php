<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SyndicateNoteToTwitter;
use App\Models\Note;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SyndicateNoteToTwitterJobTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function weSyndicateNotesToTwitter(): void
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
