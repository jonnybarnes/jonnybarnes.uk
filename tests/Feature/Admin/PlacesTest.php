<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Note;
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

        $response = $this->actingAs($user)->get('/admin/places/'.$place->id.'/edit');
        $response->assertViewIs('admin.places.edit');
    }

    #[Test]
    public function admin_can_update_place(): void
    {
        $user = User::factory()->make();
        $place = Place::factory()->create([
            'name' => 'The Bridgewater Pub',
        ]);

        $this->actingAs($user)->post('/admin/places/'.$place->id, [
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

    #[Test]
    public function merge_index_page_loads(): void
    {
        $user = User::factory()->make();
        // Use specific coordinates to avoid haversine acos overflow with extreme faker values
        $place = Place::factory()->create(['latitude' => 53.48, 'longitude' => -2.24]);

        $response = $this->actingAs($user)->get('/admin/places/'.$place->id.'/merge');
        $response->assertViewIs('admin.places.merge.index');
    }

    #[Test]
    public function merge_edit_page_loads(): void
    {
        $user = User::factory()->make();
        $place1 = Place::factory()->create();
        $place2 = Place::factory()->create();

        $response = $this->actingAs($user)->get('/admin/places/'.$place1->id.'/merge/'.$place2->id);
        $response->assertViewIs('admin.places.merge.edit');
    }

    #[Test]
    public function merge_store_with_delete_one_moves_notes_and_deletes_place1(): void
    {
        $user = User::factory()->make();
        $place1 = Place::factory()->create();
        $place2 = Place::factory()->create();
        $note = Note::factory()->create(['place_id' => $place1->id]);

        $this->actingAs($user)->post('/admin/places/merge', [
            'place1' => $place1->id,
            'place2' => $place2->id,
            'delete' => '1',
        ]);

        $this->assertDatabaseMissing('places', ['id' => $place1->id]);
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'place_id' => $place2->id]);
    }

    #[Test]
    public function merge_store_with_delete_two_moves_notes_and_deletes_place2(): void
    {
        $user = User::factory()->make();
        $place1 = Place::factory()->create();
        $place2 = Place::factory()->create();
        $note = Note::factory()->create(['place_id' => $place2->id]);

        $this->actingAs($user)->post('/admin/places/merge', [
            'place1' => $place1->id,
            'place2' => $place2->id,
            'delete' => '2',
        ]);

        $this->assertDatabaseMissing('places', ['id' => $place2->id]);
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'place_id' => $place1->id]);
    }
}
