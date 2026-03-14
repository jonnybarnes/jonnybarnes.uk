<?php

declare(strict_types=1);

namespace App\Services\Micropub\Data;

use Illuminate\Http\Request;

class UpdateData extends MicropubData
{
    public function __construct(
        public readonly array $tokenData,
        public readonly ?string $updateUrl,
        public readonly ?array $updateReplace,
        public readonly ?array $updateAdd,
        public readonly ?array $updateDelete,
    ) {}

    public static function fromRequest(Request $request): static
    {
        if ($request->isJson()) {
            $data = $request->json()->all();

            return new static(
                tokenData: $data['token_data'],
                updateUrl: $data['url'] ?? null,
                updateReplace: $data['replace'] ?? null,
                updateAdd: $data['add'] ?? null,
                updateDelete: $data['delete'] ?? null,
            );
        }

        return new static(
            tokenData: $request->input('token_data'),
            updateUrl: $request->input('url'),
            updateReplace: $request->input('replace'),
            updateAdd: $request->input('add'),
            updateDelete: $request->input('delete'),
        );
    }

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
