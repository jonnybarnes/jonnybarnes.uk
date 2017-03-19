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
    public function test_client_page_see_authenticated()
    {
        $this->browse(function ($browser) {
            $browser->visit(route('micropub-client'))
                    ->assertSee('You are authenticated');
        });
    }

    public function test_client_page_creates_new_note()
    {
        $faker = \Faker\Factory::create();
        $note = 'Fake note from #LaravelDusk: ' . $faker->text;
        $this->browse(function ($browser) use ($note) {
            $browser->visit(route('micropub-client'))
                    ->type('content', $note)
                    ->press('Submit');
        });
        $this->assertDatabaseHas('notes', ['note' => $note]);
        $newNote = \App\Note::where('note', $note)->first();
        $newNote->forceDelete();
    }
}
