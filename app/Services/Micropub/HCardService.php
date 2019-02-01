<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use Illuminate\Support\Arr;
use App\Services\PlaceService;

class HCardService
{
    /**
     * Create a Place from h-card data, return the URL.
     *
     * @param  array  $request Data from request()->all()
     * @return string
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
        $place = resolve(PlaceService::class)->createPlace($data);

        return $place->longurl;
    }
}
