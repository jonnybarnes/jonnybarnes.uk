<?php

namespace Tests;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;

trait TestToken
{
    public function getToken()
    {
        $config = $this->app->make(Configuration::class);

        return $config->builder()
            ->issuedAt(new DateTimeImmutable())
            ->withClaim('client_id', 'https://quill.p3k.io')
            ->withClaim('me', 'https://jonnybarnes.localhost')
            ->withClaim('scope', 'create update')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    public function getTokenWithIncorrectScope()
    {
        $config = $this->app->make(Configuration::class);

        return $config->builder()
            ->issuedAt(new DateTimeImmutable())
            ->withClaim('client_id', 'https://quill.p3k.io')
            ->withClaim('me', 'https://jonnybarnes.localhost')
            ->withClaim('scope', 'view')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    public function getTokenWithNoScope()
    {
        $config = $this->app->make(Configuration::class);

        return $config->builder()
            ->issuedAt(new DateTimeImmutable())
            ->withClaim('client_id', 'https://quill.p3k.io')
            ->withClaim('me', 'https://jonnybarnes.localhost')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    public function getInvalidToken()
    {
        $token = $this->getToken();

        return substr($token, 0, -5);
    }
}
