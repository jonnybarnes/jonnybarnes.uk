<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SyndicateNoteToMastodon;
use App\Models\Note;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyndicateNoteToMastodonJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function we_syndicate_notes_to_mastodon(): void
    {
        $faker = Factory::create();
        $randomNumber = $faker->randomNumber();
        $mastodonUrl = 'https://mastodon.example/@jonny/'.$randomNumber;
        Http::fake([
            'brid.gy/*' => Http::response([
                'url' => $mastodonUrl,
                'id' => (string) $randomNumber,
                'type' => ['h-entry'],
            ], 201),
        ]);

        $note = Note::factory()->create();
        $job = new SyndicateNoteToMastodon($note);
        $job->handle();

        $this->assertDatabaseHas('notes', [
            'mastodon_url' => $mastodonUrl,
        ]);
    }

    #[Test]
    public function we_post_the_correct_source_and_target(): void
    {
        Http::fake([
            'brid.gy/*' => Http::response([
                'url' => 'https://mastodon.example/@jonny/1',
            ], 201),
        ]);

        $note = Note::factory()->create(['note' => 'This is a **test**']);
        $job = new SyndicateNoteToMastodon($note);
        $job->handle();

        Http::assertSent(function (Request $request) use ($note) {
            return $request->url() === 'https://brid.gy/publish/webmention'
                && $request['source'] === $note->uri
                && $request['target'] === 'https://brid.gy/publish/mastodon';
        });
    }

    #[Test]
    public function a_bridgy_failure_throws_and_does_not_set_mastodon_url(): void
    {
        Http::fake([
            'brid.gy/*' => Http::response([
                'error' => 'Could not find target link',
            ], 400),
        ]);

        $note = Note::factory()->create();
        $job = new SyndicateNoteToMastodon($note);

        $this->expectException(\RuntimeException::class);

        try {
            $job->handle();
        } finally {
            $this->assertDatabaseHas('notes', [
                'id' => $note->id,
                'mastodon_url' => null,
            ]);
        }
    }
}
