<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\IndieAuthService;

class IndieAuthServiceTest extends TestCase
{
    /**
     * Test the getAuthorizationEndpoint method.
     *
     * @return void
     */
    public function test_indieauthservice_getauthorizationendpoint_method()
    {
        $service = new IndieAuthService();
        $result = $service->getAuthorizationEndpoint(config('app.url'));
        $this->assertEquals('https://indieauth.com/auth', $result);
    }

    /**
     * Test the getAuthorizationEndpoint method returns null on failure.
     *
     * @return void
     */
    public function test_indieauthservice_getauthorizationendpoint_method_returns_null_on_failure()
    {
        $service = new IndieAuthService();
        $result = $service->getAuthorizationEndpoint('http://example.org');
        $this->assertEquals(null, $result);
    }

    /**
     * Test that the Service build the correct redirect URL.
     *
     * @return void
     */
    public function test_indieauthservice_builds_correct_redirect_url()
    {
        $service = new IndieAuthService();
        $result = $service->buildAuthorizationURL(
            'https://indieauth.com/auth',
            config('app.url')
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
        $service = new IndieAuthService();
        $result = $service->getTokenEndpoint(config('app.url'));
        $this->assertEquals(config('app.url') . '/api/token', $result);
    }

    /**
     * Test the discoverMicropubEndpoint method.
     *
     * @return void
     */
    public function test_indieauthservice_discovermicropubendpoint_method()
    {
        $service = new IndieAuthService();
        $result = $service->discoverMicropubEndpoint(config('app.url'));
        $this->assertEquals(config('app.url') . '/api/post', $result);
    }
}
