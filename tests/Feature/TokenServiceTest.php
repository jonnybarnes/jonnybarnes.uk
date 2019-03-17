<?php

namespace Tests\Feature;

use Tests\TestCase;
use Lcobucci\JWT\Builder;
use App\Services\TokenService;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use App\Exceptions\InvalidTokenException;

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
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token failed validation');

        $data = [
            'me' => 'https://example.org',
            'client_id' => 'https://quill.p3k.io',
            'scope' => 'post'
        ];
        $signer = new Sha256();
        $token = (new Builder())->set('me', $data['me'])
            ->set('client_id', $data['client_id'])
            ->set('scope', $data['scope'])
            ->set('date_issued', time())
            ->set('nonce', bin2hex(random_bytes(8)))
            ->sign($signer, 'r4ndomk3y')
            ->getToken();

        $service = new TokenService();
        $token = $service->validateToken($token);
    }
}
