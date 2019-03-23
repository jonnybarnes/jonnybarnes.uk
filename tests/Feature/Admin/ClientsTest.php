<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ClientsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_page()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
                         ->get('/admin/clients');
        $response->assertSeeText('Clients');
    }

    public function test_create_page()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
                         ->get('/admin/clients/create');
        $response->assertSeeText('New Client');
    }

    public function test_create_new_client()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user)
             ->post('/admin/clients', [
                 'client_name' => 'Micropublish',
                 'client_url' => 'https://micropublish.net'
             ]);
        $this->assertDatabaseHas('clients', [
            'client_name' => 'Micropublish',
            'client_url' => 'https://micropublish.net'
        ]);
    }

    public function test_see_edit_form()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
                         ->get('/admin/clients/1/edit');
        $response->assertSee('https://jbl5.dev/notes/new');
    }

    public function test_edit_client()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user)
             ->post('/admin/clients/1', [
                 '_method' => 'PUT',
                 'client_url' => 'https://jbl5.dev/notes/new',
                 'client_name' => 'JBL5dev',
             ]);
        $this->assertDatabaseHas('clients', [
            'client_url' => 'https://jbl5.dev/notes/new',
            'client_name' => 'JBL5dev',
        ]);
    }

    public function test_delete_client()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user)
             ->post('/admin/clients/1', [
                 '_method' => 'DELETE',
             ]);
        $this->assertDatabaseMissing('clients', [
            'client_url' => 'https://jbl5.dev/notes/new',
        ]);
    }
}
