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
    public function notesPageLoads(): void
    {
        $response = $this->get('/notes');
        $response->assertStatus(200);
    }

    /**
     * Test a specific note.
     */
    #[Test]
    public function specificNotePageLoads(): void
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
    public function oldNoteUrlsRedirect(): void
    {
        $note = Note::factory()->create();
        $response = $this->get('/note/' . $note->id);
        $response->assertRedirect($note->uri);
    }

    /**
     * Visit the tagged page and check the tag view data.
     */
    #[Test]
    public function taggedNotesPageLoads(): void
    {
        $response = $this->get('/notes/tagged/beer');
        $response->assertViewHas('tag', 'beer');
    }

    #[Test]
    public function unknownNoteGives404(): void
    {
        $response = $this->get('/notes/112233');
        $response->assertNotFound();
    }

    #[Test]
    public function checkNoteIdNotOutOfRange(): void
    {
        $response = $this->get('/notes/photou-photologo');
        $response->assertNotFound();
    }
}
