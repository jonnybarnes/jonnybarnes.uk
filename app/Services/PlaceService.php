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
    public function createplace(Request $request)
    {
        //we’ll either have latitude and longitude sent together in a
        //geo-url (micropub), or seperatley (/admin)
        if ($request->input('geo') !== null) {
            $parts = explode(':', $request->input('geo'));
            $latlng = explode(',', $parts[1]);
            $latitude = $latlng[0];
            $longitude = $latlng[1];
        }
        if ($request->input('latitude') !== null) {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
        }
        $place = new Place();
        $place->name = $request->input('name');
        $place->description = $request->input('description');
        $place->location = new Point((float) $latitude, (float) $longitude);
        $place->save();

        return $place;
    }
}
