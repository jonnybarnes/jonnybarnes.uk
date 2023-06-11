<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the `/notes` page returns 200, this should
     * mean the database is being hit.
     *
     * @test
     */
    public function notesPageLoads(): void
    {
        $response = $this->get('/notes');
        $response->assertStatus(200);
    }

    /**
     * Test a specific note.
     *
     * @test
     */
    public function specificNotePageLoads(): void
    {
        $note = Note::factory()->create();
        $response = $this->get($note->longurl);
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
     *
     * @test
     */
    public function oldNoteUrlsRedirect(): void
    {
        $note = Note::factory()->create();
        $response = $this->get('/note/' . $note->id);
        $response->assertRedirect($note->longurl);
    }

    /**
     * Visit the tagged page and check the tag view data.
     *
     * @test
     */
    public function taggedNotesPageLoads(): void
    {
        $response = $this->get('/notes/tagged/beer');
        $response->assertViewHas('tag', 'beer');
    }

    /** @test */
    public function unknownNoteGives404(): void
    {
        $response = $this->get('/notes/112233');
        $response->assertNotFound();
    }

    /** @test */
    public function checkNoteIdNotOutOfRange(): void
    {
        $response = $this->get('/notes/photou-photologo');
        $response->assertNotFound();
    }
}
