<?php

use App\Place;
use Illuminate\Database\Seeder;
use Phaza\LaravelPostgis\Geometries\Point;

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
        $place->location = new Point('53.4983', '-2.3805');
        $place->external_urls = json_encode([
            'foursqaure' => 'https://foursqaure.com/v/123435/the-bridgewater-pub',
            'osm' => 'https://www.openstreetmap.org/way/987654',
        ]);
        $place->save();
    }
}
