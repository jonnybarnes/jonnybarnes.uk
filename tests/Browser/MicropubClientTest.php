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
            $browser->visit('/micropub/create')
                    ->assertSee('You are authenticated');
        });
    }

    public function test_client_page_updates_syndication()
    {
        $this->browse(function ($browser) use ($note) {
            $browser->visit(route('micropub-client'))
                    ->clickLink('Refresh Syndication Targets')
                    ->assertSee('jonnybarnes');
        });
    }
}
