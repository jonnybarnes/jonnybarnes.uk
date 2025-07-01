<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlacesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function places_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/places');
        $response->assertViewIs('admin.places.index');
    }

    #[Test]
    public function create_place_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/places/create');
        $response->assertViewIs('admin.places.create');
    }

    #[Test]
    public function admin_can_create_new_place(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)->post('/admin/places', [
            'name' => 'Test Place',
            'description' => 'A dummy place for feature tests',
            'latitude' => '1.23',
            'longitude' => '4.56',
        ]);
        $this->assertDatabaseHas('places', [
            'name' => 'Test Place',
            'description' => 'A dummy place for feature tests',
        ]);
    }

    #[Test]
    public function edit_place_page_loads(): void
    {
        $user = User::factory()->make();
        $place = Place::factory()->create();

        $response = $this->actingAs($user)->get('/admin/places/' . $place->id . '/edit');
        $response->assertViewIs('admin.places.edit');
    }

    #[Test]
    public function admin_can_update_place(): void
    {
        $user = User::factory()->make();
        $place = Place::factory()->create([
            'name' => 'The Bridgewater Pub',
        ]);

        $this->actingAs($user)->post('/admin/places/' . $place->id, [
            '_method' => 'PUT',
            'name' => 'The Bridgewater',
            'description' => 'Who uses “Pub” anyway',
            'latitude' => '53.4983',
            'longitude' => '-2.3805',
        ]);
        $this->assertDatabaseHas('places', [
            'name' => 'The Bridgewater',
        ]);
    }
}
