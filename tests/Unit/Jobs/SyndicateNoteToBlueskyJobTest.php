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
        $faker = Factory::create();
        $randomNumber = $faker->randomNumber();
        $blueskyUrl = 'https://bsky.app/profile/jonnybarnes.uk/'.$randomNumber;
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'url' => $blueskyUrl,
                'id' => (string) $randomNumber,
                'type' => ['h-entry'],
            ])),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create();
        $job = new SyndicateNoteToBluesky($note);
        $job->handle($client);

        $this->assertDatabaseHas('notes', [
            'bluesky_url' => $blueskyUrl,
        ]);
    }

    #[Test]
    public function we_post_the_correct_source_and_target(): void
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'url' => 'https://bsky.app/profile/jonnybarnes.uk/1',
            ])),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create(['note' => 'This is a **test**']);
        $job = new SyndicateNoteToBluesky($note);
        $job->handle($client);

        $request = $container[0]['request'];
        $body = [];
        parse_str((string) $request->getBody(), $body);

        $this->assertSame('https://brid.gy/publish/webmention', (string) $request->getUri());
        $this->assertSame($note->uri, $body['source']);
        $this->assertSame('https://brid.gy/publish/bluesky', $body['target']);
    }

    #[Test]
    public function a_bridgy_failure_throws_and_does_not_set_bluesky_url(): void
    {
        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'error' => 'Could not find target link',
            ])),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $note = Note::factory()->create();
        $job = new SyndicateNoteToBluesky($note);

        $this->expectException(\RuntimeException::class);

        try {
            $job->handle($client);
        } finally {
            $this->assertDatabaseHas('notes', [
                'id' => $note->id,
                'bluesky_url' => null,
            ]);
        }
    }
}
