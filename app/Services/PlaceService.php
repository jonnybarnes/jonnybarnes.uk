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
        if ($geo) {
            preg_match_all(
                '/([0-9\.\-]+)/',
                $geo,
                $matches
            );
            $latitude = $matches[0][0];
            $longitude = $matches[0][1];
        }
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
