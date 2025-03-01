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
    public function placesPageLoads(): void
    {
        $response = $this->get('/places');
        $response->assertStatus(200);
    }

    /**
     * Test a specific place.
     */
    #[Test]
    public function singlePlacePageLoads(): void
    {
        $place = Place::factory()->create();
        $response = $this->get($place->longurl);
        $response->assertViewHas('place', $place);
    }

    #[Test]
    public function unknownPlaceGives404()
    {
        $response = $this->get('/places/unknown');
        $response->assertNotFound();
    }
}
