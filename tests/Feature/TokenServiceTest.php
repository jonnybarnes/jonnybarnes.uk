<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
        $tokenService = new \App\Services\TokenService();
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
}
