<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Models\Note;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use App\Jobs\SyndicateNoteToFacebook;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SyndicateNoteToFacebookJobTest extends TestCase
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

        $note = Note::find(1);
        $job = new SyndicateNoteToFacebook($note);
        $job->handle($client);

        $this->assertDatabaseHas('notes', [
            'id' => 1,
            'facebook_url' => 'https://facebook.com/123',
        ]);
    }
}
