<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ClientsAdminTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_page()
    {
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin/clients');
        $response->assertSeeText('Clients');
    }

    public function test_create_page()
    {
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin/clients/create');
        $response->assertSeeText('New Client');
    }

    public function test_create_new_client()
    {
        $this->withSession(['loggedin' => true])
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
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin/clients/1/edit');
        $response->assertSee('https://jbl5.dev/notes/new');
    }

    public function test_edit_client()
    {
        $this->withSession(['loggedin' => true])
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
        $this->withSession(['loggedin' => true])
             ->post('/admin/clients/1', [
                 '_method' => 'DELETE',
             ]);
        $this->assertDatabaseMissing('clients', [
            'client_url' => 'https://jbl5.dev/notes/new',
        ]);
    }
}
