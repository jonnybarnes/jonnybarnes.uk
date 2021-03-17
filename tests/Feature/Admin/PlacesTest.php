<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlacesTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function placesPageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/places');
        $response->assertViewIs('admin.places.index');
    }

    /** @test */
    public function createPlacePageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/places/create');
        $response->assertViewIs('admin.places.create');
    }

    /** @test */
    public function adminCanCreateNewPlace(): void
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

    /** @test */
    public function editPlacePageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/places/1/edit');
        $response->assertViewIs('admin.places.edit');
    }

    /** @test */
    public function adminCanUpdatePlace(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)->post('/admin/places/1', [
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
