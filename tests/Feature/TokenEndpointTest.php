<?php

namespace Tests\Feature;

use Mockery;
use Tests\TestCase;
use IndieAuth\Client;

class TokenEndpointTest extends TestCase
{
    public function test_token_endpoint_issues_token()
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('discoverAuthorizationEndpoint')
            ->with(normalize_url(config('app.url')))
            ->once()
            ->andReturn('https://indieauth.com/auth');
        $mockClient->shouldReceive('verifyIndieAuthCode')
            ->andReturn([
                'me' => config('app.url'),
                'scope' => 'create update',
            ]);
        $this->app->instance(Client::class, $mockClient);
        $response = $this->post('/api/token', [
            'me' => config('app.url'),
            'code' => 'abc123',
            'redirect_uri' => config('app.url') . '/indieauth-callback',
            'client_id' => config('app.url') . '/micropub-client',
            'state' => mt_rand(1000, 10000),
        ]);
        parse_str($response->content(), $output);
        $this->assertEquals(config('app.url'), $output['me']);
        $this->assertTrue(array_key_exists('access_token', $output));
    }
}
