<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
