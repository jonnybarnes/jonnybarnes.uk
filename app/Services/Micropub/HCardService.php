<?php

namespace App\Services\Micropub;

use App\Services\PlaceService;

class HCardService
{
    public function process(array $request)
    {
        $data = [];
        if (array_get($request, 'properties.name')) {
            $data['name'] = array_get($request, 'properties.name');
            $data['description'] = array_get($request, 'properties.description');
            $data['geo'] = array_get($request, 'properties.geo');
        } else {
            $data['name'] = array_get($request, 'name');
            $data['description'] = array_get($request, 'description');
            $data['geo'] = array_get($request, 'geo');
            $data['latitude'] = array_get($request, 'latitude');
            $data['longitude'] = array_get($request, 'longitude');
        }
        $place = resolve(PlaceService::class)->createPlace($data);

        return $place->longurl;
    }
}
