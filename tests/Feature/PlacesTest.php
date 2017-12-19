<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Place;

class PlacesTest extends TestCase
{
    /**
     * Test the `/places` page for OK response.
     *
     * @return void
     */
    public function test_places_page()
    {
        $response = $this->get('/places');
        $response->assertStatus(200);
    }

    /**
     * Test a specific place.
     *
     * @return void
     */
    public function test_single_place()
    {
        $place = Place::where('slug', 'the-bridgewater-pub')->first();
        $response = $this->get('/places/the-bridgewater-pub');
        $response->assertViewHas('place', $place);
    }
}
