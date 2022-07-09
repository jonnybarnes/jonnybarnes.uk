<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\MicropubClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function clientsPageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
                         ->get('/admin/clients');
        $response->assertSeeText('Clients');
    }

    /** @test */
    public function adminCanLoadFormToCreateClient(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
                         ->get('/admin/clients/create');
        $response->assertSeeText('New Client');
    }

    /** @test */
    public function adminCanCreateNewClient(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)
             ->post('/admin/clients', [
                 'client_name' => 'Micropublish',
                 'client_url' => 'https://micropublish.net',
             ]);
        $this->assertDatabaseHas('clients', [
            'client_name' => 'Micropublish',
            'client_url' => 'https://micropublish.net',
        ]);
    }

    /** @test */
    public function adminCanLoadEditFormForClient(): void
    {
        $user = User::factory()->make();
        $client = MicropubClient::factory()->create([
            'client_url' => 'https://jbl5.dev/notes/new',
        ]);

        $response = $this->actingAs($user)
                         ->get('/admin/clients/' . $client->id . '/edit');
        $response->assertSee('https://jbl5.dev/notes/new');
    }

    /** @test */
    public function adminCanEditClient(): void
    {
        $user = User::factory()->make();
        $client = MicropubClient::factory()->create();

        $this->actingAs($user)
             ->post('/admin/clients/' . $client->id, [
                 '_method' => 'PUT',
                 'client_url' => 'https://jbl5.dev/notes/new',
                 'client_name' => 'JBL5dev',
             ]);
        $this->assertDatabaseHas('clients', [
            'client_url' => 'https://jbl5.dev/notes/new',
            'client_name' => 'JBL5dev',
        ]);
    }

    /** @test */
    public function adminCanDeleteClient(): void
    {
        $user = User::factory()->make();
        $client = MicropubClient::factory()->create([
            'client_url' => 'https://jbl5.dev/notes/new',
        ]);

        $this->actingAs($user)
             ->post('/admin/clients/' . $client->id, [
                 '_method' => 'DELETE',
             ]);
        $this->assertDatabaseMissing('clients', [
            'client_url' => 'https://jbl5.dev/notes/new',
        ]);
    }
}
