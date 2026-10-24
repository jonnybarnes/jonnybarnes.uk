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

    /**
     * Determine the request type: the action (e.g. "update") if one is given,
     * otherwise the microformat type without its "h-" prefix. Per the spec, a
     * request with no type is treated as an h-entry.
     */
    public function getType(): ?string
    {
        if ($this->isJson()) {
            $data = $this->json()->all();

            if (isset($data['action']) && is_string($data['action'])) {
                return $data['action'];
            }

            if (isset($data['type']) && is_array($data['type'])) {
                $type = current($data['type']);
                if (is_string($type) && str_starts_with($type, 'h-')) {
                    return substr($type, 2);
                }

                return null;
            }

            return 'entry';
        }

        if ($this->filled('action')) {
            return $this->input('action');
        }

        return $this->input('h') ?: 'entry';
    }
}
