<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SyndicateNoteToMastodon;
use App\Models\Note;
use Faker\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
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

    /** @test */
    public function weSyndicateTheOriginalMarkdown(): void
    {
        config(['bridgy.mastodon_token' => 'test']);
        $faker = Factory::create();
        $randomNumber = $faker->randomNumber();

        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(201, ['Location' => 'https://mastodon.example/@jonny/' . $randomNumber]),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $client = new Client(['handler' => $handler]);

        $note = Note::factory()->create(['note' => 'This is a **test**']);
        $job = new SyndicateNoteToMastodon($note);
        $job->handle($client);

        $this->assertDatabaseHas('notes', [
            'mastodon_url' => 'https://mastodon.example/@jonny/' . $randomNumber,
        ]);

        $expectedRequestContent = '{"type":["h-entry"],"properties":{"content":["This is a **test**"]}}';

        $this->assertEquals($expectedRequestContent, $container[0]['request']->getBody()->getContents());
    }
}
