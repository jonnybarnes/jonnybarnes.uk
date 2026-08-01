<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndieAuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_should_return_indie_auth_metadata(): void
    {
        $response = $this->get('/.well-known/indieauth-server');

        $response->assertStatus(200);
        $response->assertJson([
            'issuer' => config('app.url'),
            'authorization_endpoint' => route('indieauth.start'),
            'token_endpoint' => route('indieauth.token'),
            'code_challenge_methods_supported' => ['S256'],
            // 'introspection_endpoint' => 'introspection_endpoint',
            // 'introspection_endpoint_auth_methods_supported' => ['none'],
        ]);
    }

    #[Test]
    public function it_should_require_admin_login_to_show_authorise_form(): void
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
    public function it_should_return_approval_view_when_the_request_is_valid(): void
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
    public function it_should_return_error_view_when_respone_type_is_wrong(): void
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
    public function it_should_return_error_view_when_respone_type_is_missing(): void
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
    public function it_should_return_error_view_when_client_id_is_missing(): void
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
    public function it_should_return_error_view_when_redirect_uri_is_missing(): void
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
    public function it_should_return_error_view_when_state_is_missing(): void
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
    public function it_should_return_error_view_when_code_challenge_is_missing(): void
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
    public function it_should_return_error_view_when_code_challenge_method_is_missing(): void
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
    public function it_should_return_error_view_when_code_challenge_method_is_unsupported_value(): void
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
    public function it_should_check_client_id_for_valid_redirect(): void
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

        Http::fake([
            '*' => Http::response($appPageHtml, 200),
        ]);

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
    public function it_should_error_if_client_id_page_has_no_valid_redirect(): void
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

        Http::fake([
            '*' => Http::response($appPageHtml, 200),
        ]);

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
    public function it_should_redirect_to_app_on_approval(): void
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
        $this->assertSame('iss='.config('app.url'), $parts[2]);
    }

    #[Test]
    public function it_should_show_error_response_when_approval_request_is_missing_grant_type(): void
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
    public function it_should_show_error_response_when_approval_request_is_missing_code(): void
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
    public function it_should_show_error_response_when_approval_request_is_missing_client_id(): void
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
    public function it_should_show_error_response_when_approval_request_is_missing_redirect_uri(): void
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
    public function it_should_show_error_response_when_approval_request_is_missing_code_verifier(): void
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
    public function it_should_show_error_response_when_approval_request_grant_type_is_unsupported(): void
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
    public function it_should_return_error_for_unknown_code(): void
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
    public function it_should_return_error_for_invalid_code(): void
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
    public function it_should_return_error_for_invalid_code_verifier(): void
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
    public function it_should_return_me_data_for_valid_request(): void
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
    public function it_should_return_error_when_no_scopes_given_to_token_endpoint(): void
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
    public function it_should_return_error_when_client_id_does_not_match_during_token_request(): void
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
    public function it_should_return_an_access_token_if_validation_passes(): void
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
