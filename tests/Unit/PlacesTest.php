<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class PlacesTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function canRetrieveAssociatedNotes(): void
    {
        $place = Place::find(1);
        $this->assertInstanceOf(Collection::class, $place->notes);
    }

    /** @test */
    public function nearMethodReturnsCollection(): void
    {
        $nearby = Place::near((object) ['latitude' => 53.5, 'longitude' => -2.38], 1000)->get();
        $this->assertEquals('the-bridgewater-pub', $nearby[0]->slug);
    }

    /** @test */
    public function getLongurl(): void
    {
        $place = Place::find(1);
        $this->assertEquals(config('app.url') . '/places/the-bridgewater-pub', $place->longurl);
    }

    /** @test */
    public function getShorturl()
    {
        $place = Place::find(1);
        $this->assertEquals(config('app.shorturl') . '/places/the-bridgewater-pub', $place->shorturl);
    }

    /** @test */
    public function getUri(): void
    {
        $place = Place::find(1);
        $this->assertEquals(config('app.url') . '/places/the-bridgewater-pub', $place->uri);
    }

    /** @test */
    public function placeServiceReturnsExistingPlaceBasedOnExternalUrlsSearch(): void
    {
        $place = new Place();
        $place->name = 'Temp Place';
        $place->latitude = 37.422009;
        $place->longitude = -122.084047;
        $place->external_urls = 'https://www.openstreetmap.org/way/1234';
        $place->save();
        $service = new PlaceService();
        $ret = $service->createPlaceFromCheckin([
            'properties' => [
                'url' => ['https://www.openstreetmap.org/way/1234'],
            ]
        ]);
        $this->assertInstanceOf('App\Models\Place', $ret); // a place was returned
        $this->assertCount(12, Place::all()); // still 12 places
    }

    /** @test */
    public function placeServiceRequiresNameWhenCreatingNewPlace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required name');

        $service = new PlaceService();
        $service->createPlaceFromCheckin(['foo' => 'bar']);
    }

    /** @test */
    public function placeServiceRequiresLatitudeWhenCreatingNewPlace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required longitude/latitude');

        $service = new PlaceService();
        $service->createPlaceFromCheckin(['properties' => ['name' => 'bar']]);
    }

    /** @test */
    public function placeServcieCanupdateExternalUrls(): void
    {
        $place = Place::find(1);
        $place->external_urls = 'https://bridgewater.pub';
        $this->assertEquals('{"osm":"https:\/\/www.openstreetmap.org\/way\/987654","foursquare":"https:\/\/foursquare.com\/v\/123435\/the-bridgewater-pub","default":"https:\/\/bridgewater.pub"}', $place->external_urls);
    }
}
