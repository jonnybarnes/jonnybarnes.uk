<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class NotesControllerTest extends TestCase
{
    /*
     * Test the `/notes` page returns 200, this should
     * mean the database is being hit.
     *
     * @test
     *
    public function notesPageLoads(): void
    {
        $response = $this->get('/notes');
        $response->assertStatus(200);
    }*/

    /**
     * Test a specific note.
     *
     * @test
     */
    public function specificNotePageLoads(): void
    {
        $response = $this->get('/notes/D');
        $response->assertViewHas('note');
    }

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
        $response = $this->get('/note/11');
        $response->assertRedirect(config('app.url') . '/notes/B');
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
    public function unknownNoteGives404()
    {
        $response = $this->get('/notes/112233');
        $response->assertNotFound();
    }
}
