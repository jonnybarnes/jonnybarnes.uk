<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class MicropubClientTest extends DuskTestCase
{
    /**
     * Test the client is shown for an unauthorised request.
     *
     * @return void
     */
    public function test_client_page_see_me_url()
    {
        $this->browse(function ($browser) {
            $browser->visit('/micropub/create')
                    ->assertSee(config('app.url'));
        });
    }

    public function test_client_page_creates_new_note()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $this->browse(function ($browser) use ($note) {
            $browser->visit('/micropub/create')
                    ->type('content', $note)
                    ->press('submit');
            $this->assertDatabaseHas('notes', ['note' => $note]);
        });
        //reset database
        $newNote = \App\Note::where('note', $note)->first();
        $newNote->forceDelete();
    }
}
