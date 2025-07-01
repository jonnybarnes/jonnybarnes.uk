<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlacesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the `/places` page for OK response.
     */
    #[Test]
    public function places_page_loads(): void
    {
        $response = $this->get('/places');
        $response->assertStatus(200);
    }

    /**
     * Test a specific place.
     */
    #[Test]
    public function single_place_page_loads(): void
    {
        $place = Place::factory()->create();
        $response = $this->get($place->uri);
        $response->assertViewHas('place', $place);
    }

    #[Test]
    public function unknown_place_gives404()
    {
        $response = $this->get('/places/unknown');
        $response->assertNotFound();
    }
}
