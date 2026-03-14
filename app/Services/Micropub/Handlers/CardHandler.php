<?php

declare(strict_types=1);

namespace App\Services\Micropub\Handlers;

use App\Exceptions\InvalidTokenScopeException;
use App\Services\Micropub\Data\CardData;
use App\Services\Micropub\Data\MicropubData;
use App\Services\PlaceService;

class CardHandler implements MicropubHandlerInterface
{
    public function dataClass(): string
    {
        return CardData::class;
    }

    /**
     * @throws InvalidTokenScopeException
     */
    public function handle(MicropubData $data): array
    {
        assert($data instanceof CardData);

        $scopes = $data->tokenData['scope'];
        if (is_string($scopes)) {
            $scopes = explode(' ', $scopes);
        }

        if (! in_array('create', $scopes, true)) {
            throw new InvalidTokenScopeException;
        }

        $location = resolve(PlaceService::class)->createPlace($data->toArray())->uri;

        return [
            'response' => 'created',
            'url' => $location,
        ];
    }
}
