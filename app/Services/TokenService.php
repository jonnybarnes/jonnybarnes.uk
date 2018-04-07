<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\AddClientToDatabase;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use App\Exceptions\InvalidTokenException;
use Lcobucci\JWT\{Builder, Parser, Token};

class TokenService
{
    /**
     * Generate a JWT token.
     *
     * @param  array The data to be encoded
     * @return string The signed token
     */
    public function getNewToken(array $data): string
    {
        $signer = new Sha256();
        $token = (new Builder())->set('me', $data['me'])
            ->set('client_id', $data['client_id'])
            ->set('scope', $data['scope'])
            ->set('date_issued', time())
            ->set('nonce', bin2hex(random_bytes(8)))
            ->sign($signer, config('app.key'))
            ->getToken();
        dispatch(new AddClientToDatabase($data['client_id']));

        return (string) $token;
    }

    /**
     * Check the token signature is valid.
     *
     * @param  string The token
     * @return \Lcobucci\JWT\Token
     */
    public function validateToken(string $bearerToken): Token
    {
        $signer = new Sha256();
        try {
            $token = (new Parser())->parse((string) $bearerToken);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidTokenException('Token could not be parsed');
        }
        if (! $token->verify($signer, config('app.key'))) {
            throw new InvalidTokenException('Token failed validation');
        }

        return $token;
    }
}
