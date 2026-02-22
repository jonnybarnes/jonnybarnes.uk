<?php

declare(strict_types=1);

namespace App\Services\Micropub\Data;

class UpdateData extends MicropubData
{
    public function __construct(
        public readonly array $tokenData,
        public readonly ?string $updateUrl,
        public readonly ?array $updateReplace,
        public readonly ?array $updateAdd,
        public readonly ?array $updateDelete,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            tokenData: $data['token_data'],
            updateUrl: $data['update_url'] ?? null,
            updateReplace: $data['update_replace'] ?? null,
            updateAdd: $data['update_add'] ?? null,
            updateDelete: $data['update_delete'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'token_data' => $this->tokenData,
            'update_url' => $this->updateUrl,
            'update_replace' => $this->updateReplace,
            'update_add' => $this->updateAdd,
            'update_delete' => $this->updateDelete,
        ];
    }
}
