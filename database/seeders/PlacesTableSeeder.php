<?php

namespace Database\Seeders;

use App\Models\Place;
use Illuminate\Database\Seeder;

class PlacesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $place = new Place();
        $place->name = 'The Bridgewater Pub';
        $place->description = 'A lovely local pub with a decent selection of cask ales';
        $place->latitude = 53.4983;
        $place->longitude = -2.3805;
        $place->external_urls = 'https://foursquare.com/v/123435/the-bridgewater-pub';
        $place->external_urls = 'https://www.openstreetmap.org/way/987654';
        $place->save();

        Place::factory(10)->create();
    }
}
