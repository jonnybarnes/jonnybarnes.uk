<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Note;
use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlacesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function canRetrieveAssociatedNotes(): void
    {
        $place = Place::factory()->create();
        Note::factory(5)->create([
            'place_id' => $place->id,
        ]);
        $this->assertInstanceOf(Collection::class, $place->notes);
        $this->assertCount(5, $place->notes);
    }

    #[Test]
    public function nearMethodReturnsCollection(): void
    {
        Place::factory()->create([
            'name' => 'The Bridgewater Pub',
            'latitude' => 53.4983,
            'longitude' => -2.3805,
        ]);
        $nearby = Place::near((object) ['latitude' => 53.5, 'longitude' => -2.38])->get();
        $this->assertEquals('the-bridgewater-pub', $nearby[0]->slug);
    }

    #[Test]
    public function getUri(): void
    {
        $place = Place::factory()->create([
            'name' => 'The Bridgewater Pub',
        ]);
        $this->assertEquals(config('app.url') . '/places/the-bridgewater-pub', $place->uri);
    }

    #[Test]
    public function placeServiceReturnsExistingPlaceBasedOnExternalUrlsSearch(): void
    {
        Place::factory(10)->create();

        $place = new Place;
        $place->name = 'Temp Place';
        $place->latitude = 37.422009;
        $place->longitude = -122.084047;
        $place->external_urls = 'https://www.openstreetmap.org/way/1234';
        $place->save();
        $service = new PlaceService;
        $ret = $service->createPlaceFromCheckin([
            'properties' => [
                'url' => ['https://www.openstreetmap.org/way/1234'],
            ],
        ]);
        $this->assertCount(11, Place::all());
    }

    #[Test]
    public function placeServiceRequiresNameWhenCreatingNewPlace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required name');

        $service = new PlaceService;
        $service->createPlaceFromCheckin(['foo' => 'bar']);
    }

    #[Test]
    public function placeServiceRequiresLatitudeWhenCreatingNewPlace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required longitude/latitude');

        $service = new PlaceService;
        $service->createPlaceFromCheckin(['properties' => ['name' => 'bar']]);
    }

    #[Test]
    public function placeServiceCanUpdateExternalUrls(): void
    {
        $place = Place::factory()->create([
            'name' => 'The Bridgewater Pub',
            'latitude' => 53.4983,
            'longitude' => -2.3805,
            'external_urls' => '',
        ]);
        $place->external_urls = 'https://www.openstreetmap.org/way/987654';
        $place->external_urls = 'https://foursquare.com/v/123435/the-bridgewater-pub';
        $place->save();

        $place->external_urls = 'https://bridgewater.pub';
        $this->assertEquals(json_encode([
            'default' => 'https://bridgewater.pub',
            'osm' => 'https://www.openstreetmap.org/way/987654',
            'foursquare' => 'https://foursquare.com/v/123435/the-bridgewater-pub',
        ]), $place->external_urls);
    }
}
