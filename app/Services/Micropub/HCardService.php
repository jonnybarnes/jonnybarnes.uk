<?php

namespace App\Services\Micropub;

use Illuminate\Http\Request;
use App\Services\PlaceService;

class HCardService
{
    public function process(Request $request)
    {
        $data = [];
        if ($request->header('Content-Type') == 'application/json') {
            $data['name'] = $request->input('properties.name');
            $data['description'] = $request->input('properties.description') ?? null;
            if ($request->has('properties.geo')) {
                $data['geo'] = $request->input('properties.geo');
            }
        } else {
            $data['name'] = $request->input('name');
            $data['description'] = $request->input('description');
            if ($request->has('geo')) {
                $data['geo'] = $request->input('geo');
            }
            if ($request->has('latitude')) {
                $data['latitude'] = $request->input('latitude');
                $data['longitude'] = $request->input('longitude');
            }
        }
        $place = resolve(PlaceService::class)->createPlace($data);

        return $place->longurl;
    }
}
