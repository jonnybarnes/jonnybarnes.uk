<?php

namespace Tests\Feature;

use App\Note;
use Tests\TestCase;

class NotesControllerTest extends TestCase
{
    /**
     * Test the `/notes` page returns 200, this should
     * mean the database is being hit.
     *
     * @return void
     */
    public function test_notes_page()
    {
        $response = $this->get('/notes');
        $response->assertStatus(200);
    }

    /**
     * Test a specific note.
     *
     * @return void
     */
    public function test_specific_note()
    {
        $response = $this->get('/notes/D');
        $response->assertViewHas('note');
    }

    public function test_note_replying_to_tweet()
    {
        $response = $this->get('/notes/B');
        $response->assertViewHas('note');
    }

    /**
     * Test that `/note/{decID}` redirects to `/notes/{nb60id}`.
     *
     * @return void
     */
    public function test_dec_id_redirect()
    {
        $response = $this->get('/note/11');
        $response->assertRedirect(config('app.url') . '/notes/B');
    }

    /**
     * Visit the tagged page and check the tag view data.
     *
     * @return void
     */
    public function test_tagged_notes_page()
    {
        $response = $this->get('/notes/tagged/beer');
        $response->assertViewHas('tag', 'beer');
    }
}
