<?php

namespace Tests\Feature\Admin;

use App\Contact;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\UploadedFile;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactsTest extends TestCase
{
    use DatabaseTransactions;

    public function tearDown()
    {
        if (file_exists(public_path() . '/assets/profile-images/tantek.com/image')) {
            unlink(public_path() . '/assets/profile-images/tantek.com/image');
            rmdir(public_path() . '/assets/profile-images/tantek.com');
        }
        parent::tearDown();
    }

    public function test_index_page()
    {
        $response = $this->withSession([
            'loggedin' => true
        ])->get('/admin/contacts');
        $response->assertViewIs('admin.contacts.index');
    }

    public function test_create_page()
    {
        $response = $this->withSession([
            'loggedin' => true
        ])->get('/admin/contacts/create');
        $response->assertViewIs('admin.contacts.create');
    }

    public function test_create_new_contact()
    {
        $this->withSession([
            'loggedin' => true
        ])->post('/admin/contacts', [
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

    public function test_see_edit_form()
    {
        $response = $this->withSession([
            'loggedin' => true
        ])->get('/admin/contacts/1/edit');
        $response->assertViewIs('admin.contacts.edit');
    }

    public function test_update_contact_no_uploaded_avatar()
    {
        $this->withSession([
            'loggedin' => true
        ])->post('/admin/contacts/1', [
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

    public function test_edit_contact_with_uploaded_avatar()
    {
        copy(__DIR__ . '/../../aaron.png', sys_get_temp_dir() . '/tantek.png');
        $path = sys_get_temp_dir() . '/tantek.png';
        $file = new UploadedFile($path, 'tantek.png', 'image/png', filesize($path), null, true);
        $this->withSession([
            'loggedin' => true
        ])->post('/admin/contacts/1', [
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

    public function test_delete_contact()
    {
        $this->withSession([
            'loggedin' => true
        ])->post('/admin/contacts/1', [
            '_method' => 'DELETE',
        ]);
        $this->assertDatabaseMissing('contacts', [
            'nick' => 'tantek',
        ]);
    }

    public function test_get_avatar_method()
    {
        $html = <<<HTML
<div class="h-card">
    <img class="u-photo" src="http://tantek.com/tantek.png">
</div>
HTML;
        $file = fopen(__DIR__ . '/../../aaron.png', 'r');
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/html'], $html),
            new Response(200, ['Content-Type' => 'iamge/png'], $file),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);

        $response = $this->withSession([
            'loggedin' => true,
        ])->get('/admin/contacts/1/getavatar');

        $this->assertFileEquals(
            __DIR__ . '/../../aaron.png',
            public_path() . '/assets/profile-images/tantek.com/image'
        );
    }

    public function test_get_avatar_method_redirects_with_failed_homepage()
    {
        $mock = new MockHandler([
            new Response(404),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->app->instance(Client::class, $client);

        $response = $this->withSession([
            'loggedin' => true,
        ])->get('/admin/contacts/1/getavatar');

        $response->assertRedirect('/admin/contacts/1/edit');
    }

    public function test_get_avatar_method_redirects_with_failed_avatar_download()
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

        $response = $this->withSession([
            'loggedin' => true,
        ])->get('/admin/contacts/1/getavatar');

        $response->assertRedirect('/admin/contacts/1/edit');
    }

    public function test_get_avatar_for_contact_with_no_homepage()
    {
        $contact = Contact::create([
            'nick' => 'fred',
            'name' => 'Fred Bloggs',
        ]);

        $response = $this->withSession([
            'loggedin' => true,
        ])->get('/admin/contacts/' . $contact->id . '/getavatar');

        $response->assertRedirect('/admin/contacts/' . $contact->id . '/edit');
    }
}
