<?php

declare(strict_types=1);

namespace App\Services;

use App\Place;
use Illuminate\Http\Request;
use Phaza\LaravelPostgis\Geometries\Point;

class PlaceService
{
    /**
     * Create a place.
     *
     * @param  array $data
     * @return \App\Place
     */
    public function createPlace(array $data): Place
    {
        //obviously a place needs a lat/lng, but this could be sent in a geo-url
        //if no geo array key, we assume the array already has lat/lng values
        if (array_key_exists('geo', $data)) {
            preg_match_all(
                '/([0-9\.\-]+)/',
                $data['geo'],
                $matches
            );
            $data['latitude'] = $matches[0][0];
            $data['longitude'] = $matches[0][1];
        }
        $place = new Place();
        $place->name = $data['name'];
        $place->description = $data['description'];
        $place->location = new Point((float) $data['latitude'], (float) $data['longitude']);
        $place->save();

        return $place;
    }
}
