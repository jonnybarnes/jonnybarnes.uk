<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\AddClientToDatabase;
use App\Models\MicropubToken;

class TokenService
{
    /**
     * Generate a new bearer token.
     */
    public function getNewToken(array $data): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        MicropubToken::create([
            'token_hash' => hash('sha256', $token),
            'client_id' => $data['client_id'],
            'me' => $data['me'],
            'scope' => $data['scope'],
        ]);

        dispatch(new AddClientToDatabase($data['client_id']));

        return $token;
    }
}
