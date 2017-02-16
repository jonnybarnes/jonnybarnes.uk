<?php

namespace App\Tests;

use BrowserKitTest;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IndieAuthTest extends BrowserKitTest
{
    protected $appurl;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
    }

    /**
     * Test the getAuthorizationEndpoint calls the correct service methods,
     * though these methods are actually mocked.
     *
     * @return void
     */
    public function testIndieAuthServiceDiscoversEndpoint()
    {
        $service = new \App\Services\IndieAuthService();
        $client = new \IndieAuth\Client();
        $result = $service->getAuthorizationEndpoint($this->appurl, $client);
        $this->assertSame('https://indieauth.com/auth', $result);
    }

    /**
     * Test that the Service build the correct redirect URL.
     *
     * @return void
     */
    public function testIndieAuthServiceBuildRedirectURL()
    {
        $client = new \IndieAuth\Client();
        $service = new \App\Services\IndieAuthService();
        $result = $service->buildAuthorizationURL(
            'https://indieauth.com/auth',
            $this->appurl,
            $client
        );
        $this->assertSame(
            'https://indieauth.com/auth?me=',
            substr($result, 0, 30)
        );
    }

    /**
     * Test the `start` method redirects to the client on error.
     *
     * @return void
     */
    public function testIndieAuthControllerBeginAuthRedirectsToClientOnFail()
    {
        $response = $this->call('GET', $this->appurl . '/indieauth/start', ['me' => 'http://example.org']);
        $this->assertSame($this->appurl . '/micropub/create', $response->headers->get('Location'));
    }

    /**
     * Now we test the `start` method as a whole.
     *
     * @return void
     */
    public function testIndieAuthControllerBeginAuthRedirectsToEndpoint()
    {
        $response = $this->call('GET', $this->appurl . '/indieauth/start', ['me' => $this->appurl]);
        $this->assertSame(
            'https://indieauth.com/auth?me=',
            substr($response->headers->get('Location'), 0, 30)
        );
        $response = null;
    }
}
