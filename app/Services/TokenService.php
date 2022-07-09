<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\AddClientToDatabase;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;

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
        $config = resolve(Configuration::class);

        $token = $config->builder()
            ->issuedAt(new DateTimeImmutable())
            ->withClaim('client_id', $data['client_id'])
            ->withClaim('me', $data['me'])
            ->withClaim('scope', $data['scope'])
            ->withClaim('nonce', bin2hex(random_bytes(8)))
            ->getToken($config->signer(), $config->signingKey());

        dispatch(new AddClientToDatabase($data['client_id']));

        return $token->toString();
    }

    /**
     * Check the token signature is valid.
     *
     * @param  string The token
     * @return mixed
     */
    public function validateToken(string $bearerToken): Token
    {
        $config = resolve('Lcobucci\JWT\Configuration');

        $token = $config->parser()->parse($bearerToken);

        $constraints = $config->validationConstraints();

        $config->validator()->assert($token, ...$constraints);

        return $token;
    }
}
