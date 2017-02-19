<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IndieAuthServiceTest extends TestCase
{
    /**
     * Test the getAuthorizationEndpoint method.
     *
     * @return void
     */
    public function test_indieauthservice_getauthorizationendpoint_method()
    {
        $service = new \App\Services\IndieAuthService();
        $client = new \IndieAuth\Client();
        $result = $service->getAuthorizationEndpoint(config('app.url'), $client);
        $this->assertEquals('https://indieauth.com/auth', $result);
    }

    /**
     * Test that the Service build the correct redirect URL.
     *
     * @return void
     */
    public function test_indieauthservice_builds_correct_redirect_url()
    {
        $service = new \App\Services\IndieAuthService();
        $client = new \IndieAuth\Client();
        $result = $service->buildAuthorizationURL(
            'https://indieauth.com/auth',
            config('app.url'),
            $client
        );
        $this->assertEquals(
            'https://indieauth.com/auth?me=',
            substr($result, 0, 30)
        );
    }

    /**
     * Test the getTokenEndpoint method.
     *
     * @return void
     */
    public function test_indieauthservice_gettokenendpoint_method()
    {
        $service = new \App\Services\IndieAuthService();
        $client = new \IndieAuth\Client();
        $result = $service->getTokenEndpoint(config('app.url'), $client);
        $this->assertEquals(config('app.url') . '/api/token', $result);
    }

    /**
     * Test the discoverMicropubEndpoint method.
     *
     * @return void
     */
    public function test_indieauthservice_discovermicropubendpoint_method()
    {
        $service = new \App\Services\IndieAuthService();
        $client = new \IndieAuth\Client();
        $result = $service->discoverMicropubEndpoint(config('app.url'), $client);
        $this->assertEquals(config('app.url') . '/api/post', $result);
    }
}
