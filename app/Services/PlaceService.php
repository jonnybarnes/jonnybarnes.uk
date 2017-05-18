<?php

declare(strict_types=1);

namespace App\Services;

use App\Place;
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

    /**
     * Create a place from a h-card checkin, for exameple from OwnYourSwarm.
     *
     * @param  array
     * @return bool
     */
    public function createPlaceFromCheckin(array $checkin): bool
    {
        //check if the place exists if from swarm
        if (array_key_exists('url', $checkin['properties'])) {
            $search = Place::where('foursquare', $checkin['properties']['url'][0])->count();
            if ($search === 1) {
                return true;
            }
        }
        if (array_key_exists('name', $checkin['properties']) === false) {
            throw new \InvalidArgumentException('Missing required name');
        }
        if (array_key_exists('latitude', $checkin['properties']) === false) {
            throw new \InvalidArgumentException('Missing required longitude/latitude');
        }
        $place = new Place();
        $place->name = $checkin['properties']['name'][0];
        if (starts_with($checkin['properties']['url'][0], 'https://foursquare.com')) {
            $place->foursquare = $checkin['properties']['url'][0];
        }
        $place->location = new Point(
            (float) $checkin['properties']['latitude'][0],
            (float) $checkin['properties']['longitude'][0]
        );
        $place->save();

        return true;
    }
}
