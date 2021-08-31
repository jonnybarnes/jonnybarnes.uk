<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SyndicateNoteToTwitter;
use App\Models\Note;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyndicateNoteToTwitterJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function weSyndicateNotesToTwitter(): void
    {
        $faker = \Faker\Factory::create();
        $randomNumber = $faker->randomNumber();
        $json = json_encode([
            'url' => 'https://twitter.com/i/web/status/' . $randomNumber,
        ]);
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], $json),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create();
        $job = new SyndicateNoteToTwitter($note);
        $job->handle($client);

        $this->assertDatabaseHas('notes', [
            'tweet_id' => $randomNumber,
        ]);
    }
}
