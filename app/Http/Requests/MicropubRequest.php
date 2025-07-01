<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class MicropubRequest extends FormRequest
{
    protected array $micropubData = [];

    public function rules(): array
    {
        return [
            // Validation rules
        ];
    }

    public function getMicropubData(): array
    {
        return $this->micropubData;
    }

    public function getType(): ?string
    {
        // Return consistent type regardless of input format
        return $this->micropubData['type'] ?? null;
    }

    protected function prepareForValidation(): void
    {
        // Normalize the request data based on content type
        if ($this->isJson()) {
            $this->normalizeMicropubJson();
        } else {
            $this->normalizeMicropubForm();
        }
    }

    private function normalizeMicropubJson(): void
    {
        $json = $this->json();
        if ($json === null) {
            throw new \InvalidArgumentException('`isJson()` passed but there is no json data');
        }

        $data = $json->all();

        // Convert JSON type (h-entry) to simple type (entry)
        if (isset($data['type']) && is_array($data['type'])) {
            $type = current($data['type']);
            if (strpos($type, 'h-') === 0) {
                $this->micropubData['type'] = substr($type, 2);
            }
        }
        // Or set the type to update
        elseif (isset($data['action']) && $data['action'] === 'update') {
            $this->micropubData['type'] = 'update';
        }

        // Add in the token data
        $this->micropubData['token_data'] = $data['token_data'];

        // Add h-entry values
        $this->micropubData['content'] = Arr::get($data, 'properties.content.0');
        $this->micropubData['in-reply-to'] = Arr::get($data, 'properties.in-reply-to.0');
        $this->micropubData['published'] = Arr::get($data, 'properties.published.0');
        $this->micropubData['location'] = Arr::get($data, 'location');
        $this->micropubData['bookmark-of'] = Arr::get($data, 'properties.bookmark-of.0');
        $this->micropubData['like-of'] = Arr::get($data, 'properties.like-of.0');
        $this->micropubData['mp-syndicate-to'] = Arr::get($data, 'properties.mp-syndicate-to');

        // Add h-card values
        $this->micropubData['name'] = Arr::get($data, 'properties.name.0');
        $this->micropubData['description'] = Arr::get($data, 'properties.description.0');
        $this->micropubData['geo'] = Arr::get($data, 'properties.geo.0');

        // Add checkin value
        $this->micropubData['checkin'] = Arr::get($data, 'checkin');
        $this->micropubData['syndication'] = Arr::get($data, 'properties.syndication.0');
    }

    private function normalizeMicropubForm(): void
    {
        // Convert form h=entry to type=entry
        if ($h = $this->input('h')) {
            $this->micropubData['type'] = $h;
        }

        // Add some fields to the micropub data with default null values
        $this->micropubData['in-reply-to'] = null;
        $this->micropubData['published'] = null;
        $this->micropubData['location'] = null;
        $this->micropubData['description'] = null;
        $this->micropubData['geo'] = null;
        $this->micropubData['latitude'] = null;
        $this->micropubData['longitude'] = null;

        // Map form fields to micropub data
        foreach ($this->except(['h', 'access_token']) as $key => $value) {
            $this->micropubData[$key] = $value;
        }
    }
}
