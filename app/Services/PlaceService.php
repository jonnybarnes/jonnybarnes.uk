<?php

namespace App\Services;

use App\Place;
use Illuminate\Http\Request;
use Phaza\LaravelPostgis\Geometries\Point;

class PlaceService
{
    /**
     * Create a place.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \App\Place
     */
    public function createPlace(Request $request)
    {
        if ($request->header('Content-Type') == 'application/json') {
            $name = $request->input('properties.name');
            $description = $request->input('properties.description') ?? null;
            $geo = $request->input('properties.geo');
        } else {
            $name = $request->input('name');
            $description = $request->input('description');
            $geo = $request->input('geo');
        }
        $parts = explode(':', $geo);
        $latlng = explode(',', $parts[1]);
        $latitude = $latlng[0];
        $longitude = $latlng[1];
        if ($request->input('latitude') !== null) {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
        }
        $place = new Place();
        $place->name = $name;
        $place->description = $description;
        $place->location = new Point((float) $latitude, (float) $longitude);
        $place->save();

        return $place;
    }
}
