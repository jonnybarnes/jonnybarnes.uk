<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Contact;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    /** @test */
    public function contactIndexPageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/contacts');
        $response->assertViewIs('admin.contacts.index');
    }

    /** @test */
    public function contactCreatePageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/contacts/create');
        $response->assertViewIs('admin.contacts.create');
    }

    /** @test */
    public function adminCanCreateNewContact(): void
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
            'homepage' => 'https://fred.blog/gs'
        ]);
    }

    /** @test */
    public function adminCanSeeFormToEditContact(): void
    {
        $user = User::factory()->make();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->get('/admin/contacts/' . $contact->id . '/edit');
        $response->assertViewIs('admin.contacts.edit');
    }

    /** @test */
    public function adminCanUpdateContact(): void
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

    /** @test */
    public function adminCanEditContactAndUploadAvatar(): void
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

    /** @test */
    public function adminCanDeleteContact(): void
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

    /** @test */
    public function adminCanTriggerRetrievalOfRemoteAvatar(): void
    {
        $html = <<<HTML
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

    /** @test */
    public function gettingRemoteAvatarFailsGracefullyWithRemoteNotFound(): void
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

    /** @test */
    public function gettingRemoteAvatarFailsGracefullyWithRemoteError(): void
    {
        $html = <<<HTML
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

    /** @test */
    public function gettingRemoteAvatarFailsGracefullyForContactWithNoHompage(): void
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
