<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class NotesTest extends DuskTestCase
{
    /**
     * Look for the client name after the note.
     *
     * @return void
     */
    public function test_client_name_displayed()
    {
        $this->browse(function ($browser) {
            $browser->visit('/notes/D')
                    ->assertSee('JBL5');
        });
    }

    /**
     * Look for the client URL after the note.
     *
     * @return void
     */
    public function test_client_url_displayed()
    {
        $this->browse(function ($browser) {
            $browser->visit('/notes/E')
                    ->assertSee('quill.p3k.io');
        });
    }
}
