<?php

namespace Tests;

use App\Services\TokenService;

trait TestToken
{
    public function getToken(): string
    {
        return $this->app->make(TokenService::class)->getNewToken([
            'client_id' => 'https://quill.p3k.io',
            'me' => 'http://jonnybarnes.localhost',
            'scope' => 'create update',
        ]);
    }

    public function getTokenWithIncorrectScope(): string
    {
        return $this->app->make(TokenService::class)->getNewToken([
            'client_id' => 'https://quill.p3k.io',
            'me' => 'https://jonnybarnes.localhost',
            'scope' => 'view',
        ]);
    }

    public function getTokenWithNoScope(): string
    {
        return $this->app->make(TokenService::class)->getNewToken([
            'client_id' => 'https://quill.p3k.io',
            'me' => 'https://jonnybarnes.localhost',
            'scope' => '',
        ]);
    }

    public function getInvalidToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
