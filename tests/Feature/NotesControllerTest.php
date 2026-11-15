<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Note;
use App\Models\WebMention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the `/notes` page returns 200, this should
     * mean the database is being hit.
     */
    #[Test]
    public function notes_page_loads(): void
    {
        $response = $this->get('/notes');
        $response->assertStatus(200);
    }

    /**
     * Test a specific note.
     */
    #[Test]
    public function specific_note_page_loads(): void
    {
        $note = Note::factory()->create();
        $response = $this->get($note->uri);
        $response->assertViewHas('note');
    }

    /** @todo */
    /* @test *
    public function noteReplyingToTweet(): void
    {
        $response = $this->get('/notes/B');
        $response->assertViewHas('note');
    }*/

    /**
     * Test that `/note/{decID}` redirects to `/notes/{nb60id}`.
     */
    #[Test]
    public function old_note_urls_redirect(): void
    {
        $note = Note::factory()->create();
        $response = $this->get('/note/'.$note->id);
        $response->assertRedirect($note->uri);
    }

    /**
     * Visit the tagged page and check the tag view data.
     */
    #[Test]
    public function tagged_notes_page_loads(): void
    {
        $response = $this->get('/notes/tagged/beer');
        $response->assertViewHas('tag', 'beer');
    }

    #[Test]
    public function unknown_note_gives404(): void
    {
        $response = $this->get('/notes/112233');
        $response->assertNotFound();
    }

    #[Test]
    public function check_note_id_not_out_of_range(): void
    {
        $response = $this->get('/notes/photou-photologo');
        $response->assertNotFound();
    }

    /**
     * Notes not yet syndicated should carry the hidden Bridgy publish
     * verification links, so Bridgy can confirm intent when it re-fetches
     * the page after receiving our webmention.
     */
    #[Test]
    public function note_page_includes_bridgy_verification_links_when_not_yet_syndicated(): void
    {
        $note = Note::factory()->create();

        $response = $this->get($note->uri);

        $response->assertSee('https://brid.gy/publish/mastodon', false);
        $response->assertSee('https://brid.gy/publish/bluesky', false);
    }

    /**
     * Once a note has been syndicated to a silo, the hidden verification
     * link for that silo should no longer be rendered.
     */
    #[Test]
    public function note_page_omits_bridgy_verification_link_once_syndicated(): void
    {
        $note = Note::factory()->create();
        $note->mastodon_url = 'https://mastodon.example/@jonny/1';
        $note->bluesky_url = 'https://bsky.app/profile/jonnybarnes.uk/1';
        $note->save();

        $response = $this->get($note->uri);

        $response->assertDontSee('https://brid.gy/publish/mastodon', false);
        $response->assertDontSee('https://brid.gy/publish/bluesky', false);
    }

    private function mentionOn(Note $note, string $type, ?array $author, array $mf2): WebMention
    {
        return WebMention::factory()->create([
            'commentable_id' => $note->id,
            'commentable_type' => Note::class,
            'target' => $note->uri,
            'type' => $type,
            'mf2' => json_encode($mf2),
            'author' => $author,
        ]);
    }

    #[Test]
    public function note_page_renders_a_reply_whose_author_is_a_plain_name(): void
    {
        Http::fake();
        $note = Note::factory()->create();
        $this->mentionOn($note, 'in-reply-to', ['type' => ['h-card'], 'properties' => ['name' => ['Fred Bloggs']]], [
            'items' => [[
                'type' => ['h-entry'],
                'properties' => ['author' => ['Fred Bloggs'], 'content' => [['html' => 'Nice note', 'value' => 'Nice note']]],
            ]],
        ]);

        $this->get($note->uri)
            ->assertOk()
            ->assertSee('Fred Bloggs')
            ->assertSee('Nice note');
        Http::assertNothingSent();
    }

    #[Test]
    public function note_page_renders_mentions_without_an_author(): void
    {
        Http::fake();
        $note = Note::factory()->create();
        $noEntry = ['items' => [['type' => ['h-card'], 'properties' => ['name' => ['Someone']]]]];
        $this->mentionOn($note, 'in-reply-to', null, $noEntry);
        $this->mentionOn($note, 'like-of', null, $noEntry);
        $this->mentionOn($note, 'repost-of', null, $noEntry);

        $this->get($note->uri)
            ->assertOk()
            ->assertSee('Unknown author')
            ->assertSee('/assets/profile-images/default-image');
        Http::assertNothingSent();
    }

    #[Test]
    public function note_page_does_not_fetch_author_pages(): void
    {
        Http::fake();
        $note = Note::factory()->create();
        // An author given only as a URL would previously be fetched on render
        $this->mentionOn($note, 'like-of', null, [
            'items' => [['type' => ['h-entry'], 'properties' => ['author' => ['https://example.org/']]]],
            'rels' => ['author' => ['https://example.org/']],
        ]);

        $this->get($note->uri)->assertOk();
        Http::assertNothingSent();
    }
}
