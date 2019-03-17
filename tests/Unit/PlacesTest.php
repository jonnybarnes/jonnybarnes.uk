<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Place;
use App\Services\PlaceService;
use Phaza\LaravelPostgis\Geometries\Point;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PlacesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_notes_method()
    {
        $place = Place::find(1);
        $this->assertInstanceOf(Collection::class, $place->notes);
    }

    /**
     * Test the near method returns a collection.
     *
     * @return void
     */
    public function test_near_method()
    {
        $nearby = Place::near(new Point(53.5, -2.38), 1000)->get();
        $this->assertEquals('the-bridgewater-pub', $nearby[0]->slug);
    }

    public function test_longurl_method()
    {
        $place = Place::find(1);
        $this->assertEquals(config('app.url') . '/places/the-bridgewater-pub', $place->longurl);
    }

    public function test_uri_method()
    {
        $place = Place::find(1);
        $this->assertEquals(config('app.url') . '/places/the-bridgewater-pub', $place->uri);

    }

    public function test_shorturl_method()
    {
        $place = Place::find(1);
        $this->assertEquals(config('app.shorturl') . '/places/the-bridgewater-pub', $place->shorturl);
    }

    public function test_service_returns_existing_place()
    {
        $place = new Place();
        $place->name = 'Temp Place';
        $place->location = new Point(37.422009, -122.084047);
        $place->external_urls = 'https://www.openstreetmap.org/way/1234';
        $place->save();
        $service = new PlaceService();
        $ret = $service->createPlaceFromCheckin([
            'properties' => [
                'url' => ['https://www.openstreetmap.org/way/1234'],
            ]
        ]);
        $this->assertInstanceOf('App\Models\Place', $ret); // a place was returned
        $this->assertEquals(2, count(Place::all())); // still 2 places
    }

    public function test_service_requires_name()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required name');

        $service = new PlaceService();
        $service->createPlaceFromCheckin(['foo' => 'bar']);
    }

    public function test_service_requires_latitude()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required longitude/latitude');

        $service = new PlaceService();
        $service->createPlaceFromCheckin(['properties' => ['name' => 'bar']]);
    }

    public function test_updating_external_urls()
    {
        $place = Place::find(1);
        $place->external_urls = 'https://bridgewater.pub';
        $this->assertEquals('{"osm":"https:\/\/www.openstreetmap.org\/way\/987654","foursquare":"https:\/\/foursquare.com\/v\/123435\/the-bridgewater-pub","default":"https:\/\/bridgewater.pub"}', $place->external_urls);
    }
}
