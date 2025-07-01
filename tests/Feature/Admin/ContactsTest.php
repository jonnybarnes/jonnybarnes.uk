<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Contact;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (file_exists(public_path() . '/assets/profile-images/tantek.com/image')) {
            unlink(public_path() . '/assets/profile-images/tantek.com/image');
            rmdir(public_path() . '/assets/profile-images/tantek.com');
        }
        parent::tearDown();
    }

    #[Test]
    public function contact_index_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/contacts');
        $response->assertViewIs('admin.contacts.index');
    }

    #[Test]
    public function contact_create_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/contacts/create');
        $response->assertViewIs('admin.contacts.create');
    }

    #[Test]
    public function admin_can_create_new_contact(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)->post('/admin/contacts', [
            'name' => 'Fred Bloggs',
            'nick' => 'fred',
            'homepage' => 'https://fred.blog/gs',
        ]);
        $this->assertDatabaseHas('contacts', [
            'name' => 'Fred Bloggs',
            'nick' => 'fred',
            'homepage' => 'https://fred.blog/gs',
        ]);
    }

    #[Test]
    public function admin_can_see_form_to_edit_contact(): void
    {
        $user = User::factory()->make();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->get('/admin/contacts/' . $contact->id . '/edit');
        $response->assertViewIs('admin.contacts.edit');
    }

    #[Test]
    public function admin_can_update_contact(): void
    {
        $user = User::factory()->make();
        $contact = Contact::factory()->create();

        $this->actingAs($user)->post('/admin/contacts/' . $contact->id, [
            '_method' => 'PUT',
            'name' => 'Tantek Celik',
            'nick' => 'tantek',
            'homepage' => 'https://tantek.com',
            'twitter' => 't',
        ]);
        $this->assertDatabaseHas('contacts', [
            'name' => 'Tantek Celik',
            'homepage' => 'https://tantek.com',
        ]);
    }

    #[Test]
    public function admin_can_edit_contact_and_upload_avatar(): void
    {
        copy(__DIR__ . '/../../aaron.png', sys_get_temp_dir() . '/tantek.png');
        $path = sys_get_temp_dir() . '/tantek.png';
        $file = new UploadedFile($path, 'tantek.png', 'image/png', null, true);
        $user = User::factory()->make();
        $contact = Contact::factory()->create();

        $this->actingAs($user)->post('/admin/contacts/' . $contact->id, [
            '_method' => 'PUT',
            'name' => 'Tantek Celik',
            'nick' => 'tantek',
            'homepage' => 'https://tantek.com',
            'twitter' => 't',
            'avatar' => $file,
        ]);
        $this->assertFileEquals(
            __DIR__ . '/../../aaron.png',
            public_path() . '/assets/profile-images/tantek.com/image'
        );
    }

    #[Test]
    public function admin_can_delete_contact(): void
    {
        $user = User::factory()->make();
        $contact = Contact::factory()->create(['nick' => 'tantek']);

        $this->assertDatabaseHas('contacts', [
            'nick' => 'tantek',
        ]);

        $this->actingAs($user)->post('/admin/contacts/' . $contact->id, [
            '_method' => 'DELETE',
        ]);
        $this->assertDatabaseMissing('contacts', [
            'nick' => 'tantek',
        ]);
    }

    #[Test]
    public function admin_can_trigger_retrieval_of_remote_avatar(): void
    {
        $html = <<<'HTML'
        <div class="h-card">
            <img class="u-photo" alt="" src="http://tantek.com/tantek.png">
        </div>
        HTML;
        $file = fopen(__DIR__ . '/../../aaron.png', 'rb');
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/html'], $html),
            new Response(200, ['Content-Type' => 'image/png'], $file),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
        $user = User::factory()->make();
        $contact = Contact::factory()->create([
            'homepage' => 'https://tantek.com',
        ]);

        $this->actingAs($user)->get('/admin/contacts/' . $contact->id . '/getavatar');

        $this->assertFileEquals(
            __DIR__ . '/../../aaron.png',
            public_path() . '/assets/profile-images/tantek.com/image'
        );
    }

    #[Test]
    public function getting_remote_avatar_fails_gracefully_with_remote_not_found(): void
    {
        $mock = new MockHandler([
            new Response(404),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
        $user = User::factory()->make();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->get('/admin/contacts/' . $contact->id . '/getavatar');

        $response->assertRedirect('/admin/contacts/' . $contact->id . '/edit');
    }

    #[Test]
    public function getting_remote_avatar_fails_gracefully_with_remote_error(): void
    {
        $html = <<<'HTML'
        <div class="h-card">
            <img class="u-photo" src="http://tantek.com/tantek.png">
        </div>
        HTML;
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/html'], $html),
            new Response(404),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);
        $user = User::factory()->make();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->get('/admin/contacts/' . $contact->id . '/getavatar');

        $response->assertRedirect('/admin/contacts/' . $contact->id . '/edit');
    }

    #[Test]
    public function getting_remote_avatar_fails_gracefully_for_contact_with_no_hompage(): void
    {
        $contact = Contact::create([
            'nick' => 'fred',
            'name' => 'Fred Bloggs',
        ]);
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/contacts/' . $contact->id . '/getavatar');

        $response->assertRedirect('/admin/contacts/' . $contact->id . '/edit');
    }
}
