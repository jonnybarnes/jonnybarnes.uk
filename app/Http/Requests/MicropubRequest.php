<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MicropubRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Validation rules
        ];
    }

    public function getType(): ?string
    {
        if ($this->isJson()) {
            $data = $this->json()->all();

            if (isset($data['action']) && $data['action'] === 'update') {
                return 'update';
            }

            if (isset($data['type']) && is_array($data['type'])) {
                $type = current($data['type']);
                if (str_starts_with($type, 'h-')) {
                    return substr($type, 2);
                }
            }

            return null;
        }

        return $this->input('h') ?: null;
    }
}
