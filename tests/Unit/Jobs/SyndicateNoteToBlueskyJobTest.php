<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SyndicateNoteToBluesky;
use App\Models\Note;
use Faker\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyndicateNoteToBlueskyJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function we_syndicate_notes_to_bluesky(): void
    {
        config(['bridgy.bluesky_token' => 'test']);
        $faker = Factory::create();
        $randomNumber = $faker->randomNumber();
        $mock = new MockHandler([
            new Response(201, ['Location' => 'https://bsky.app/profile/jonnybarnes.uk/'.$randomNumber]),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create();
        $job = new SyndicateNoteToBluesky($note);
        $job->handle($client);

        $this->assertDatabaseHas('notes', [
            'bluesky_url' => 'https://bsky.app/profile/jonnybarnes.uk/'.$randomNumber,
        ]);
    }

    #[Test]
    public function we_syndicate_the_original_markdown_to_bluesky(): void
    {
        config(['bridgy.bluesky_token' => 'test']);
        $faker = Factory::create();
        $randomNumber = $faker->randomNumber();

        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(201, ['Location' => 'https://bsky.app/profile/jonnybarnes.uk/'.$randomNumber]),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create(['note' => 'This is a **test**']);
        $job = new SyndicateNoteToBluesky($note);
        $job->handle($client);

        $this->assertDatabaseHas('notes', [
            'bluesky_url' => 'https://bsky.app/profile/jonnybarnes.uk/'.$randomNumber,
        ]);

        $expectedRequestContent = '{"type":["h-entry"],"properties":{"content":["This is a **test**"]}}';

        $this->assertEquals($expectedRequestContent, $container[0]['request']->getBody()->getContents());
    }
}
