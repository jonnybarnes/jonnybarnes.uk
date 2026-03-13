<?php

declare(strict_types=1);

namespace App\Services\Micropub\Data;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CardData extends MicropubData
{
    public function __construct(
        public readonly array $tokenData,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly mixed $geo,
        public readonly mixed $location,
        public readonly ?string $latitude,
        public readonly ?string $longitude,
    ) {}

    public static function fromRequest(Request $request): static
    {
        if ($request->isJson()) {
            $data = $request->json()->all();

            return new static(
                tokenData: $data['token_data'],
                name: Arr::get($data, 'properties.name.0'),
                description: Arr::get($data, 'properties.description.0'),
                geo: Arr::get($data, 'properties.geo.0'),
                location: Arr::get($data, 'properties.location'),
                latitude: null,
                longitude: null,
            );
        }

        return new static(
            tokenData: $request->input('token_data'),
            name: $request->input('name'),
            description: $request->input('description'),
            geo: $request->input('geo'),
            location: $request->input('location'),
            latitude: $request->input('latitude'),
            longitude: $request->input('longitude'),
        );
    }

    public static function fromArray(array $data): static
    {
        return new static(
            tokenData: $data['token_data'],
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            geo: $data['geo'] ?? null,
            location: $data['location'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'token_data' => $this->tokenData,
            'name' => $this->name,
            'description' => $this->description,
            'geo' => $this->geo,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
