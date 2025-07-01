<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use App\Exceptions\InvalidTokenScopeException;
use App\Services\PlaceService;

class CardHandler implements MicropubHandlerInterface
{
    /**
     * @throws InvalidTokenScopeException
     */
    public function handle(array $data): array
    {
        // Handle h-card requests
        $scopes = $data['token_data']['scope'];
        if (is_string($scopes)) {
            $scopes = explode(' ', $scopes);
        }

        if (! in_array('create', $scopes, true)) {
            throw new InvalidTokenScopeException;
        }

        $location = resolve(PlaceService::class)->createPlace($data)->uri;

        return [
            'response' => 'created',
            'url' => $location,
        ];
    }
}
