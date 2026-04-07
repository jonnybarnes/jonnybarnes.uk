<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\MicropubClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function clients_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/admin/clients');
        $response->assertSeeText('Clients');
    }

    #[Test]
    public function admin_can_load_form_to_create_client(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/admin/clients/create');
        $response->assertSeeText('New Client');
    }

    #[Test]
    public function admin_can_create_new_client(): void
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

    #[Test]
    public function admin_can_load_edit_form_for_client(): void
    {
        $user = User::factory()->make();
        $client = MicropubClient::factory()->create([
            'client_url' => 'https://jbl5.dev/notes/new',
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/clients/'.$client->id.'/edit');
        $response->assertSee('https://jbl5.dev/notes/new');
    }

    #[Test]
    public function admin_can_edit_client(): void
    {
        $user = User::factory()->make();
        $client = MicropubClient::factory()->create();

        $this->actingAs($user)
            ->post('/admin/clients/'.$client->id, [
                '_method' => 'PUT',
                'client_url' => 'https://jbl5.dev/notes/new',
                'client_name' => 'JBL5dev',
            ]);
        $this->assertDatabaseHas('clients', [
            'client_url' => 'https://jbl5.dev/notes/new',
            'client_name' => 'JBL5dev',
        ]);
    }

    #[Test]
    public function admin_can_delete_client(): void
    {
        $user = User::factory()->make();
        $client = MicropubClient::factory()->create([
            'client_url' => 'https://jbl5.dev/notes/new',
        ]);

        $this->actingAs($user)
            ->post('/admin/clients/'.$client->id, [
                '_method' => 'DELETE',
            ]);
        $this->assertDatabaseMissing('clients', [
            'client_url' => 'https://jbl5.dev/notes/new',
        ]);
    }
}
