<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndieAuthTest extends TestCase
{
    use RefreshDatabase;

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
            'scopes' => 'create update delete',
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
            'scopes' => 'create update delete',
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
            'scopes' => 'create update delete',
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
            'scopes' => 'create update delete',
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
            'scopes' => 'create update delete',
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
            'scopes' => 'create update delete',
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
            'scopes' => 'create update delete',
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
            'scopes' => 'create update delete',
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
            'scopes' => 'create update delete',
            'code_challenge' => '123456',
            'code_challenge_method' => 'invalid_value',
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(200);
        $response->assertViewIs('indieauth.error');
        $response->assertSee('only a code_challenge_method of "S256" is supported');
    }
}
