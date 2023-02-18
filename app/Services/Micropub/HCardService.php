<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use App\Services\PlaceService;
use Illuminate\Support\Arr;

class HCardService
{
    /**
     * Create a Place from h-card data, return the URL.
     */
    public function process(array $request): string
    {
        $data = [];
        if (Arr::get($request, 'properties.name')) {
            $data['name'] = Arr::get($request, 'properties.name');
            $data['description'] = Arr::get($request, 'properties.description');
            $data['geo'] = Arr::get($request, 'properties.geo');
        } else {
            $data['name'] = Arr::get($request, 'name');
            $data['description'] = Arr::get($request, 'description');
            $data['geo'] = Arr::get($request, 'geo');
            $data['latitude'] = Arr::get($request, 'latitude');
            $data['longitude'] = Arr::get($request, 'longitude');
        }

        return resolve(PlaceService::class)->createPlace($data)->longurl;
    }
}
