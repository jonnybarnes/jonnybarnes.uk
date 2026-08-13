<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MicropubToken;
use App\Services\TokenService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    /**
     * Given the token is dependent on a random value and stored only as a
     * hash, to test, we shall create a token, and then verify it.
     */
    #[Test]
    public function tokenservice_creates_valid_tokens(): void
    {
        $tokenService = new TokenService;
        $data = [
            'me' => 'https://example.org',
            'client_id' => 'https://quill.p3k.io',
            'scope' => 'post',
        ];
        $token = $tokenService->getNewToken($data);

        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer '.$token]);

        $response->assertJson([
            'response' => 'token',
            'token' => [
                'me' => $data['me'],
                'client_id' => $data['client_id'],
                'scope' => $data['scope'],
            ],
        ]);
    }

    #[Test]
    public function unknown_tokens_are_not_valid(): void
    {
        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer '.bin2hex(random_bytes(32))]);

        $response->assertJson([
            'response' => 'error',
            'error' => 'invalid_token',
            'error_description' => 'The provided token did not pass validation',
        ]);
    }

    #[Test]
    public function revoked_tokens_are_not_valid(): void
    {
        $tokenService = new TokenService;
        $data = [
            'me' => 'https://example.org',
            'client_id' => 'https://quill.p3k.io',
            'scope' => 'post',
        ];
        $token = $tokenService->getNewToken($data);

        MicropubToken::where('token_hash', hash('sha256', $token))->firstOrFail()->revoke();

        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer '.$token]);

        $response->assertJson([
            'response' => 'error',
            'error' => 'invalid_token',
            'error_description' => 'The provided token did not pass validation',
        ]);
    }

    /**
     * Request input for a "string" field can be sent as an array
     * (e.g. token[]=a&token[]=b). Casting that to string throws in this app
     * (warnings are promoted to exceptions), so findActive() must guard
     * against it rather than assume its caller already validated the type.
     */
    #[Test]
    public function find_active_treats_non_string_input_as_absent(): void
    {
        $this->assertNull(MicropubToken::findActive(['a', 'b']));
        $this->assertNull(MicropubToken::findActive(null));
        $this->assertNull(MicropubToken::findActive(123));
    }
}
