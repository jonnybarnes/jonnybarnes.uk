<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Builder;
use InvalidArgumentException;
use Lcobucci\JWT\Signer\Hmac\Sha256;

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

        return (string) $token;
    }

    /**
     * Check the token signature is valid.
     *
     * @param  string The token
     * @return mixed
     */
    public function validateToken(string $token): ?Token
    {
        $signer = new Sha256();
        try {
            $token = (new Parser())->parse((string) $token);
        } catch (InvalidArgumentException | RuntimeException $e) {
            return null;
        }
        if ($token->verify($signer, config('app.key'))) {
            //signuture valid
            return $token;
        }

        return null;
    }
}
