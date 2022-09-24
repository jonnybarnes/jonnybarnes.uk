<?php

declare(strict_types=1);

namespace Tests\Feature;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use IndieAuth\Client as IndieAuthClient;
use JsonException;
use Mockery;
use Tests\TestCase;

class TokenEndpointTest extends TestCase
{
    /**
     * @test
     * @throws JsonException
     * @throws Exception
     */
    public function tokenEndpointIssuesToken(): void
    {
        $mockIndieAuthClient = Mockery::mock(IndieAuthClient::class);
        $mockIndieAuthClient->shouldReceive('discoverAuthorizationEndpoint')
            ->with(normalize_url(config('app.url')))
            ->once()
            ->andReturn('https://indieauth.com/auth');
        $mockHandler = new MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'me' => config('app.url'),
                'scope' => 'create update',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockGuzzleClient = new GuzzleClient(['handler' => $handlerStack]);
        $this->app->instance(IndieAuthClient::class, $mockIndieAuthClient);
        $this->app->instance(GuzzleClient::class, $mockGuzzleClient);
        $response = $this->post('/api/token', [
            'me' => config('app.url'),
            'code' => 'abc123',
            'redirect_uri' => config('app.url') . '/indieauth-callback',
            'client_id' => config('app.url') . '/micropub-client',
            'state' => random_int(1000, 10000),
        ]);
        $response->assertJson([
            'me' => config('app.url'),
            'scope' => 'create update',
        ]);
    }

    /**
     * @test
     * @throws JsonException
     * @throws Exception
     */
    public function tokenEndpointReturnsErrorWhenAuthEndpointLacksMeData(): void
    {
        $mockIndieAuthClient = Mockery::mock(IndieAuthClient::class);
        $mockIndieAuthClient->shouldReceive('discoverAuthorizationEndpoint')
            ->with(normalize_url(config('app.url')))
            ->once()
            ->andReturn('https://indieauth.com/auth');
        $mockHandler = new MockHandler([
            new \GuzzleHttp\Psr7\Response(400, [], json_encode([
                'error' => 'error_message',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockGuzzleClient = new GuzzleClient(['handler' => $handlerStack]);
        $this->app->instance(IndieAuthClient::class, $mockIndieAuthClient);
        $this->app->instance(GuzzleClient::class, $mockGuzzleClient);
        $response = $this->post('/api/token', [
            'me' => config('app.url'),
            'code' => 'abc123',
            'redirect_uri' => config('app.url') . '/indieauth-callback',
            'client_id' => config('app.url') . '/micropub-client',
            'state' => random_int(1000, 10000),
        ]);
        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'There was an error verifying the IndieAuth code',
        ]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function tokenEndpointReturnsErrorWhenNoAuthEndpointFound(): void
    {
        $mockIndieAuthClient = Mockery::mock(IndieAuthClient::class);
        $mockIndieAuthClient->shouldReceive('discoverAuthorizationEndpoint')
            ->with(normalize_url(config('app.url')))
            ->once()
            ->andReturn(null);
        $this->app->instance(IndieAuthClient::class, $mockIndieAuthClient);
        $response = $this->post('/api/token', [
            'me' => config('app.url'),
            'code' => 'abc123',
            'redirect_uri' => config('app.url') . '/indieauth-callback',
            'client_id' => config('app.url') . '/micropub-client',
            'state' => random_int(1000, 10000),
        ]);
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Could not discover the authorization endpoint for ' . config('app.url'),
        ]);
    }
}
