<?php

declare(strict_types=1);

namespace App\Services\Micropub\Data;

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
