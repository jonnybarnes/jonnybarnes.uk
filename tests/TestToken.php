<?php

namespace Tests;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;

trait TestToken
{
    public function getToken()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('client_id', 'https://quill.p3k.io')
            ->set('me', 'https://jonnybarnes.localhost')
            ->set('scope', 'create update')
            ->set('issued_at', time())
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }

    public function getTokenWithIncorrectScope()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('client_id', 'https://quill.p3k.io')
            ->set('me', 'https://jonnybarnes.localhost')
            ->set('scope', 'view') //error here
            ->set('issued_at', time())
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }

    public function getTokenWithNoScope()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('client_id', 'https://quill.p3k.io')
            ->set('me', 'https://jonnybarnes.localhost')
            ->set('issued_at', time())
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }

    public function getInvalidToken()
    {
        $token = $this->getToken();

        return substr($token, 0, -5);
    }
}
