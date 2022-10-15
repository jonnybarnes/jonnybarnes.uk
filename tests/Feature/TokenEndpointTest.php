<?php

declare(strict_types=1);

namespace Tests\Feature;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use JsonException;
use Tests\TestCase;

class TokenEndpointTest extends TestCase
{
    /**
     * @test
     *
     * @throws JsonException
     * @throws Exception
     */
    public function tokenEndpointIssuesToken(): void
    {
        $mockHandler = new MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'me' => config('app.url'),
                'scope' => 'create update',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockGuzzleClient = new Client(['handler' => $handlerStack]);
        $this->app->instance(Client::class, $mockGuzzleClient);
        $response = $this->post('/api/token', [
            'grant_type' => 'authorization_code',
            'code' => '1234567890',
            'redirect_uri' => 'https://example.com/auth/callback',
            'client_id' => 'https://example.com',
            'code_verifier' => '1234567890',
        ]);

        $this->assertSame(config('app.url'), $response->json('me'));
        $this->assertNotEmpty($response->json('access_token'));
    }

    /**
     * @test
     *
     * @throws JsonException
     * @throws Exception
     */
    public function tokenEndpointReturnsErrorWhenAuthEndpointLacksMeData(): void
    {
        $mockHandler = new MockHandler([
            new \GuzzleHttp\Psr7\Response(400, [], json_encode([
                'error' => 'error_message',
            ], JSON_THROW_ON_ERROR)),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockGuzzleClient = new Client(['handler' => $handlerStack]);
        $this->app->instance(Client::class, $mockGuzzleClient);
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
}
