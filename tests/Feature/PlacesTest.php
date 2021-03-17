<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Place;
use Tests\TestCase;

class PlacesTest extends TestCase
{
    /**
     * Test the `/places` page for OK response.
     *
     * @test
     */
    public function placesPageLoads(): void
    {
        $response = $this->get('/places');
        $response->assertStatus(200);
    }

    /**
     * Test a specific place.
     *
     * @test
     */
    public function singlePlacePageLoads(): void
    {
        $place = Place::where('slug', 'the-bridgewater-pub')->first();
        $response = $this->get('/places/the-bridgewater-pub');
        $response->assertViewHas('place', $place);
    }

    /** @test */
    public function unknownPlaceGives404()
    {
        $response = $this->get('/places/unknown');
        $response->assertNotFound();
    }
}
