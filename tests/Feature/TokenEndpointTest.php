<?php

declare(strict_types=1);

namespace Tests\Feature;

use IndieAuth\Client;
use Mockery;
use Tests\TestCase;

class TokenEndpointTest extends TestCase
{
    /** @test */
    public function tokenEndpointIssuesToken(): void
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
        $response->assertJson([
            'me' => config('app.url'),
            'scope' => 'create update',
        ]);
    }

    /** @test */
    public function tokenEndpointReturnsErrorWhenAuthEndpointLacksMeData(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('discoverAuthorizationEndpoint')
            ->with(normalize_url(config('app.url')))
            ->once()
            ->andReturn('https://indieauth.com/auth');
        $mockClient->shouldReceive('verifyIndieAuthCode')
            ->andReturn([
                'error' => 'error_message',
            ]);
        $this->app->instance(Client::class, $mockClient);
        $response = $this->post('/api/token', [
            'me' => config('app.url'),
            'code' => 'abc123',
            'redirect_uri' => config('app.url') . '/indieauth-callback',
            'client_id' => config('app.url') . '/micropub-client',
            'state' => mt_rand(1000, 10000),
        ]);
        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'There was an error verifying the authorisation code.'
        ]);
    }

    /** @test */
    public function tokenEndpointReturnsErrorWhenNoAuthEndpointFound(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('discoverAuthorizationEndpoint')
            ->with(normalize_url(config('app.url')))
            ->once()
            ->andReturn(null);
        $this->app->instance(Client::class, $mockClient);
        $response = $this->post('/api/token', [
            'me' => config('app.url'),
            'code' => 'abc123',
            'redirect_uri' => config('app.url') . '/indieauth-callback',
            'client_id' => config('app.url') . '/micropub-client',
            'state' => mt_rand(1000, 10000),
        ]);
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Can’t determine the authorisation endpoint.']
        );
    }
}
