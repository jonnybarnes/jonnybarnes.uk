<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SyndicateNoteToMastodon;
use App\Models\Note;
use Faker\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyndicateNoteToMastodonJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function weSyndicateNotesToMastodon(): void
    {
        config(['bridgy.mastodon_token' => 'test']);
        $faker = Factory::create();
        $randomNumber = $faker->randomNumber();
        $mock = new MockHandler([
            new Response(201, ['Location' => 'https://mastodon.example/@jonny/' . $randomNumber]),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create();
        $job = new SyndicateNoteToMastodon($note);
        $job->handle($client);

        $this->assertDatabaseHas('notes', [
            'mastodon_url' => 'https://mastodon.example/@jonny/' . $randomNumber,
        ]);
    }
}
