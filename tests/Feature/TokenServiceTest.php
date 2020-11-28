<?php

namespace Tests\Feature;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Tests\TestCase;
use App\Services\TokenService;

class TokenServiceTest extends TestCase
{
    /**
     * Given the token is dependent on a random nonce, the time of creation and
     * the APP_KEY, to test, we shall create a token, and then verify it.
     *
     * @return void
     */
    public function test_token_creation_and_validation()
    {
        $tokenService = new TokenService();
        $data = [
            'me' => 'https://example.org',
            'client_id' => 'https://quill.p3k.io',
            'scope' => 'post'
        ];
        $token = $tokenService->getNewToken($data);
        $valid = $tokenService->validateToken($token);
        $validData = [
            'me' => $valid->getClaim('me'),
            'client_id' => $valid->getClaim('client_id'),
            'scope' => $valid->getClaim('scope')
        ];
        $this->assertSame($data, $validData);
    }

    public function test_token_with_different_signing_key_throws_exception()
    {
        $this->expectException(RequiredConstraintsViolated::class);

        $data = [
            'me' => 'https://example.org',
            'client_id' => 'https://quill.p3k.io',
            'scope' => 'post'
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
