<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\SendWebMentions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotesAdminTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_page()
    {
        $response = $this->withSession([
            'loggedin' => true,
        ])->get('/admin/notes');
        $response->assertViewIs('admin.notes.index');
    }

    public function test_create_page()
    {
        $response = $this->withSession([
            'loggedin' => true,
        ])->get('/admin/notes/create');
        $response->assertViewIs('admin.notes.create');
    }

    public function test_create_a_new_note()
    {
        $this->withSession([
            'loggedin' => true,
        ])->post('/admin/notes', [
            'content' => 'A new test note',
        ]);
        $this->assertDatabaseHas('notes', [
            'note' => 'A new test note',
        ]);
    }

    public function test_edit_page()
    {
        $response = $this->withSession([
            'loggedin' => true,
        ])->get('/admin/notes/1/edit');
        $response->assertViewIs('admin.notes.edit');
    }

    public function test_edit_a_note()
    {
        Queue::fake();

        $this->withSession([
            'loggedin' => true,
        ])->post('/admin/notes/1', [
            '_method' => 'PUT',
            'content' => 'An edited note',
            'webmentions' => true,
        ]);

        $this->assertDatabaseHas('notes', [
            'note' => 'An edited note',
        ]);
        Queue::assertPushed(SendWebMentions::class);
    }

    public function test_delete_note()
    {
        $this->withSession([
            'loggedin' => true,
        ])->post('/admin/notes/1', [
            '_method' => 'DELETE',
        ]);
        $this->assertSoftDeleted('notes', [
            'id' => '1',
        ]);
    }
}
