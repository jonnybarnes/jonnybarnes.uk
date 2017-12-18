<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PlacesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_page()
    {
        $response = $this->withSession([
            'loggedin' => true,
        ])->get('/admin/places');
        $response->assertViewIs('admin.places.index');
    }

    public function test_create_page()
    {
        $response = $this->withSession([
            'loggedin' => true,
        ])->get('/admin/places/create');
        $response->assertViewIs('admin.places.create');
    }

    public function test_create_new_place()
    {
        $this->withSession([
            'loggedin' => true,
        ])->post('/admin/places', [
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

    public function test_edit_page()
    {
        $response = $this->withSession([
            'loggedin' => true,
        ])->get('/admin/places/1/edit');
        $response->assertViewIs('admin.places.edit');
    }

    public function test_updating_a_place()
    {
        $this->withSession([
            'loggedin' => true,
        ])->post('/admin/places/1', [
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
