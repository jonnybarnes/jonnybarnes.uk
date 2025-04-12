<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\TokenService;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    /**
     * Given the token is dependent on a random nonce, the time of creation and
     * the APP_KEY, to test, we shall create a token, and then verify it.
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

        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer ' . $token]);

        $response->assertJson([
            'response' => 'token',
            'token' => [
                'me' => $data['me'],
                'client_id' => $data['client_id'],
                'scope' => $data['scope'],
            ]
        ]);
    }

    #[Test]
    public function tokens_with_different_signing_key_are_not_valid(): void
    {
        $data = [
            'me' => 'https://example.org',
            'client_id' => 'https://quill.p3k.io',
            'scope' => 'post',
        ];

        $config = resolve(Configuration::class);

        $token = $config->builder()
            ->issuedAt(new DateTimeImmutable)
            ->withClaim('client_id', $data['client_id'])
            ->withClaim('me', $data['me'])
            ->withClaim('scope', $data['scope'])
            ->withClaim('nonce', bin2hex(random_bytes(8)))
            ->getToken($config->signer(), InMemory::plainText(random_bytes(32)))
            ->toString();

        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer ' . $token]);

        $response->assertJson([
            'response' => 'error',
            'error' => 'invalid_token',
            'error_description' => 'The provided token did not pass validation',
        ]);
    }
}
