<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IndieAuthControllerTest extends TestCase
{
    /**
     * Test the `start` method redirects to the client on error.
     *
     * @return void
     */
    public function test_indieauthcontroller_begin_auth_flow_redirects_back_to_client_on_error()
    {
        $response = $this->call('POST', '/indieauth/start', ['me' => 'http://example.org']);
        $this->assertSame(route('micropub-client'), $response->headers->get('Location'));
    }

    /**
     * Now we test the `start` method as a whole.
     *
     * @return void
     */
    public function test_indieauthcontroller_begin_auth_redirects_to_endpoint()
    {
        $response = $this->call('POST', '/indieauth/start', ['me' => config('app.url')]);
        $this->assertSame(
            'https://indieauth.com/auth?me=',
            substr($response->headers->get('Location'), 0, 30)
        );
    }

    /**
     * Test the `callback` method.
     *
     * @return void
     */
    public function test_indieauthcontroller_callback_method_gives_error_with_mismatched_state()
    {
        $response = $this->withSession(['state' => 'state-session'])
                        ->call(
                            'GET',
                            'indieauth/callback',
                            ['me', config('app.url'), 'state' => 'request-session']
                        );
        $response->assertSessionHas(['error' => 'Invalid <code>state</code> value returned from indieauth server']);
    }
}
