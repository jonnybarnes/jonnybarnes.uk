<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\TokenService;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    /**
     * Given the token is dependent on a random nonce, the time of creation and
     * the APP_KEY, to test, we shall create a token, and then verify it.
     *
     * @test
     */
    public function tokenserviceCreatesAndValidatesTokens(): void
    {
        $tokenService = new TokenService();
        $data = [
            'me' => 'https://example.org',
            'client_id' => 'https://quill.p3k.io',
            'scope' => 'post',
        ];
        $token = $tokenService->getNewToken($data);
        $valid = $tokenService->validateToken($token);
        $validData = [
            'me' => $valid->claims()->get('me'),
            'client_id' => $valid->claims()->get('client_id'),
            'scope' => $valid->claims()->get('scope'),
        ];
        $this->assertSame($data, $validData);
    }

    /** @test */
    public function tokensWithDifferentSigningKeyThrowsException(): void
    {
        $this->expectException(RequiredConstraintsViolated::class);

        $data = [
            'me' => 'https://example.org',
            'client_id' => 'https://quill.p3k.io',
            'scope' => 'post',
        ];

        $config = resolve(Configuration::class);

        $token = $config->builder()
            ->issuedAt(new DateTimeImmutable())
            ->withClaim('client_id', $data['client_id'])
            ->withClaim('me', $data['me'])
            ->withClaim('scope', $data['scope'])
            ->withClaim('nonce', bin2hex(random_bytes(8)))
            ->getToken($config->signer(), InMemory::plainText('r4andomk3y'))
            ->toString();

        $service = new TokenService();
        $service->validateToken($token);
    }
}
