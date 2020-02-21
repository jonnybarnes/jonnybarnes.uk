<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Place;
use Illuminate\Support\Arr;
use Phaza\LaravelPostgis\Geometries\Point;

class PlaceService
{
    /**
     * Create a place.
     *
     * @param array $data
     * @return Place
     */
    public function createPlace(array $data): Place
    {
        //obviously a place needs a lat/lng, but this could be sent in a geo-url
        //if no geo array key, we assume the array already has lat/lng values
        if (array_key_exists('geo', $data) && $data['geo'] !== null) {
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
     * Create a place from a h-card checkin, for example from OwnYourSwarm.
     *
     * @param array
     * @return Place
     */
    public function createPlaceFromCheckin(array $checkin): Place
    {
        //check if the place exists if from swarm
        if (Arr::has($checkin, 'properties.url')) {
            $place = Place::whereExternalURL(Arr::get($checkin, 'properties.url.0'))->get();
            if (count($place) === 1) {
                return $place->first();
            }
        }
        if (Arr::has($checkin, 'properties.name') === false) {
            throw new \InvalidArgumentException('Missing required name');
        }
        if (Arr::has($checkin, 'properties.latitude') === false) {
            throw new \InvalidArgumentException('Missing required longitude/latitude');
        }
        $place = new Place();
        $place->name = Arr::get($checkin, 'properties.name.0');
        $place->external_urls = Arr::get($checkin, 'properties.url.0');
        $place->location = new Point(
            (float) Arr::get($checkin, 'properties.latitude.0'),
            (float) Arr::get($checkin, 'properties.longitude.0')
        );
        $place->save();

        return $place;
    }
}
