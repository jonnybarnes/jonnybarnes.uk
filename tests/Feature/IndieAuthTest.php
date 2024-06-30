<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndieAuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itShouldReturnIndieAuthMetadata(): void
    {
        $response = $this->get('/.well-known/indieauth-server');

        $response->assertStatus(200);
        $response->assertJson([
            'issuer' => config('app.url'),
            'authorization_endpoint' => route('indieauth.start'),
            'token_endpoint' => route('indieauth.token'),
            'code_challenge_methods_supported' => ['S256'],
            //'introspection_endpoint' => 'introspection_endpoint',
            //'introspection_endpoint_auth_methods_supported' => ['none'],
        ]);
    }

    #[Test]
    public function itShouldRequireAdminLoginToShowAuthoriseForm(): void
    {
        $response = $this->get('/auth', [
            'response_type' => 'code',
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'https://app.example.com/callback',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'S256',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * The test passes here because the client_id and redirect_uri are on the
     * same domain, later test will check the flow when they are different.
     */
    #[Test]
    public function itShouldReturnApprovalViewWhenTheRequestIsValid(): void
    {
        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'response_type' => 'code',
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'https://app.example.com/callback',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'S256',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.start');
    }

    #[Test]
    public function itShouldReturnErrorViewWhenResponeTypeIsWrong(): void
    {
        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'response_type' => 'invalid_value',
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'https://app.example.com/callback',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'S256',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.error');
        $response->assertSee('only a response_type of "code" is supported');
    }

    #[Test]
    public function itShouldReturnErrorViewWhenResponeTypeIsMissing(): void
    {
        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'https://app.example.com/callback',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'S256',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.error');
        $response->assertSee('response_type is required');
    }

    #[Test]
    public function itShouldReturnErrorViewWhenClientIdIsMissing(): void
    {
        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'response_type' => 'code',
            'me' => 'https://example.com',
            'redirect_uri' => 'https://app.example.com/callback',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'S256',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.error');
        $response->assertSee('client_id is required');
    }

    #[Test]
    public function itShouldReturnErrorViewWhenRedirectUriIsMissing(): void
    {
        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'response_type' => 'code',
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'S256',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.error');
        $response->assertSee('redirect_uri is required');
    }

    #[Test]
    public function itShouldReturnErrorViewWhenStateIsMissing(): void
    {
        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'response_type' => 'code',
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'https://app.example.com/callback',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'S256',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.error');
        $response->assertSee('state is required');
    }

    #[Test]
    public function itShouldReturnErrorViewWhenCodeChallengeIsMissing(): void
    {
        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'response_type' => 'code',
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'https://app.example.com/callback',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge_method' => 'S256',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.error');
        $response->assertSee('code_challenge is required');
    }

    #[Test]
    public function itShouldReturnErrorViewWhenCodeChallengeMethodIsMissing(): void
    {
        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'response_type' => 'code',
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'https://app.example.com/callback',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.error');
        $response->assertSee('code_challenge_method is required');
    }

    #[Test]
    public function itShouldReturnErrorViewWhenCodeChallengeMethodIsUnsupportedValue(): void
    {
        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'response_type' => 'code',
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'https://app.example.com/callback',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'invalid_value',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.error');
        $response->assertSee('only a code_challenge_method of "S256" is supported');
    }

    #[Test]
    public function itShouldCheckClientIdForValidRedirect(): void
    {
        // Mock Guzzle request for client_id
        $appPageHtml = <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
          <head>
            <meta charset="utf-8">
            <title>Example App</title>
            <link rel="redirect_uri" href="example-app://callback">
          </head>
          <body>
            <div class="h-app">
              <a href="/" class="u-url p-name">Example App</a>
            </div>
          </body>
        </html>
        HTML;

        $mockHandler = new MockHandler([
            new Response(200, [], $appPageHtml),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockGuzzleClient = new Client(['handler' => $handlerStack]);
        $this->app->instance(Client::class, $mockGuzzleClient);

        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'response_type' => 'code',
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'S256',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.start');
    }

    #[Test]
    public function itShouldErrorIfClientIdPageHasNoValidRedirect(): void
    {
        // Mock Guzzle request for client_id
        $appPageHtml = <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
          <head>
            <meta charset="utf-8">
            <title>Example App</title>
          </head>
          <body>
            <div class="h-app">
              <a href="/" class="u-url p-name">Example App</a>
            </div>
          </body>
        </html>
        HTML;

        $mockHandler = new MockHandler([
            new Response(200, [], $appPageHtml),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockGuzzleClient = new Client(['handler' => $handlerStack]);
        $this->app->instance(Client::class, $mockGuzzleClient);

        $user = User::factory()->make();
        $url = url()->query('/auth', [
            'response_type' => 'code',
            'me' => 'https://example.com',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'state' => '123456',
            'scope' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'S256',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.error');
        $response->assertSee('redirect_uri is not valid for this client_id');
    }

    #[Test]
    public function itShouldRedirectToAppOnApproval(): void
    {
        $user = User::factory()->make();
        $response = $this->actingAs($user)->post('/auth/confirm', [
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'state' => '123456',
            'me' => 'https://example.com',
            'scope' => [
                'create',
                'update',
                'delete',
            ],
            'code_challenge' => '123abc',
            'code_challenge_method' => 'S256',
        ]);

        $response->assertStatus(302);

        // Parse the redirect URL and check the query parameters
        // the `code` will be random, but we can check its present
        // and check the other parameters are correct
        $redirectUri = $response->headers->get('Location');
        $resolvedRedirectUri = UriResolver::resolve(new Uri('example-app://callback'), new Uri($redirectUri));
        $query = $resolvedRedirectUri->getQuery();
        $parts = explode('&', $query);
        $this->assertCount(3, $parts);
        $this->assertStringContainsString('code=', $parts[0]);
        $this->assertSame('state=123456', $parts[1]);
        $this->assertSame('iss=' . config('app.url'), $parts[2]);
    }

    #[Test]
    public function itShouldShowErrorResponseWhenApprovalRequestIsMissingGrantType(): void
    {
        $response = $this->post('/auth', [
            'code' => '123456',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => '123abc',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'grant_type' => [
                    'The grant type field is required.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldShowErrorResponseWhenApprovalRequestIsMissingCode(): void
    {
        $response = $this->post('/auth', [
            'grant_type' => 'authorization_code',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => '123abc',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'code' => [
                    'The code field is required.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldShowErrorResponseWhenApprovalRequestIsMissingClientId(): void
    {
        $response = $this->post('/auth', [
            'grant_type' => 'authorization_code',
            'code' => '123456',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => '123abc',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'client_id' => [
                    'The client id field is required.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldShowErrorResponseWhenApprovalRequestIsMissingRedirectUri(): void
    {
        $response = $this->post('/auth', [
            'grant_type' => 'authorization_code',
            'code' => '123456',
            'client_id' => 'https://app.example.com',
            'code_verifier' => '123abc',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'redirect_uri' => [
                    'The redirect uri field is required.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldShowErrorResponseWhenApprovalRequestIsMissingCodeVerifier(): void
    {
        $response = $this->post('/auth', [
            'grant_type' => 'authorization_code',
            'code' => '123456',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'code_verifier' => [
                    'The code verifier field is required.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldShowErrorResponseWhenApprovalRequestGrantTypeIsUnsupported(): void
    {
        $response = $this->post('/auth', [
            'grant_type' => 'unsupported',
            'code' => '123456',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => '123abc',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'grant_type' => [
                    'Only a grant type of "authorization_code" is supported.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldReturnErrorForUnknownCode(): void
    {
        $response = $this->post('/auth', [
            'grant_type' => 'authorization_code',
            'code' => '123456',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => '123abc',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'errors' => [
                'code' => [
                    'The code is invalid.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldReturnErrorForInvalidCode(): void
    {
        Cache::shouldReceive('pull')
            ->once()
            ->with(hash('xxh3', 'https://app.example.com'))
            ->andReturn(['auth_code' => 'some value']);

        $response = $this->post('/auth', [
            'grant_type' => 'authorization_code',
            'code' => '123456',
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => '123abc',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'code' => [
                    'The code is invalid.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldReturnErrorForInvalidCodeVerifier(): void
    {
        Cache::shouldReceive('pull')
            ->once()
            ->with(hash('xxh3', 'https://app.example.com'))
            ->andReturn([
                'auth_code' => '123456',
                'code_challenge' => '123abc',
            ]);

        $response = $this->post('/auth', [
            'grant_type' => 'authorization_code',
            'code' => '123456', // Matches auth_code we have put in the Cache
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => 'invalid_value',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'code_verifier' => [
                    'The code verifier is invalid.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldReturnMeDataForValidRequest(): void
    {
        Cache::shouldReceive('pull')
            ->once()
            ->with(hash('xxh3', 'https://app.example.com'))
            ->andReturn([
                'auth_code' => '123456',
                'code_challenge' => sodium_bin2base64(
                    hash('sha256', 'abc123def', true),
                    SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
                ),
                'client_id' => 'https://app.example.com',
                'redirect_uri' => 'example-app://callback',
            ]);

        $response = $this->post('/auth', [
            'grant_type' => 'authorization_code',
            'code' => '123456', // Matches auth_code we have put in the Cache
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => 'abc123def',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'me' => config('app.url'),
        ]);
    }

    #[Test]
    public function itShouldReturnErrorWhenNoScopesGivenToTokenEndpoint(): void
    {
        Cache::shouldReceive('pull')
            ->once()
            ->with(hash('xxh3', 'https://app.example.com'))
            ->andReturn([
                'auth_code' => '123456',
                'code_challenge' => sodium_bin2base64(
                    hash('sha256', 'abc123def', true),
                    SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
                ),
                'scope' => '',
                'client_id' => 'https://app.example.com',
                'redirect_uri' => 'example-app://callback',
            ]);

        $response = $this->post('/token', [
            'grant_type' => 'authorization_code',
            'code' => '123456', // Matches auth_code we have put in the Cache
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => 'abc123def',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'scope' => [
                    'The scope property must be non-empty for an access token to be issued.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldReturnErrorWhenClientIdDoesNotMatchDuringTokenRequest(): void
    {
        Cache::shouldReceive('pull')
            ->once()
            ->with(hash('xxh3', 'https://app.example.com'))
            ->andReturn([
                'auth_code' => '123456',
                'code_challenge' => sodium_bin2base64(
                    hash('sha256', 'abc123def', true),
                    SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
                ),
                'scope' => 'create update',
                'client_id' => 'https://app.example.invalid',
                'redirect_uri' => 'example-app://callback',
            ]);

        $response = $this->post('/token', [
            'grant_type' => 'authorization_code',
            'code' => '123456', // Matches auth_code we have put in the Cache
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => 'abc123def',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'client_id' => [
                    'The client id is invalid.',
                ],
            ],
        ]);
    }

    #[Test]
    public function itShouldReturnAnAccessTokenIfValidationPasses(): void
    {
        Cache::shouldReceive('pull')
            ->once()
            ->with(hash('xxh3', 'https://app.example.com'))
            ->andReturn([
                'auth_code' => '123456',
                'code_challenge' => sodium_bin2base64(
                    hash('sha256', 'abc123def', true),
                    SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
                ),
                'scope' => 'create update',
                'client_id' => 'https://app.example.com',
                'redirect_uri' => 'example-app://callback',
            ]);

        $response = $this->post('/token', [
            'grant_type' => 'authorization_code',
            'code' => '123456', // Matches auth_code we have put in the Cache
            'client_id' => 'https://app.example.com',
            'redirect_uri' => 'example-app://callback',
            'code_verifier' => 'abc123def',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'token_type' => 'Bearer',
            'scope' => 'create update',
            'me' => config('app.url'),
        ]);
    }
}
