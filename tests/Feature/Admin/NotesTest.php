<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\TestCase;
use App\Jobs\SendWebMentions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_page()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get('/admin/notes');
        $response->assertViewIs('admin.notes.index');
    }

    public function test_create_page()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get('/admin/notes/create');
        $response->assertViewIs('admin.notes.create');
    }

    public function test_create_a_new_note()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user)->post('/admin/notes', [
            'content' => 'A new test note',
        ]);
        $this->assertDatabaseHas('notes', [
            'note' => 'A new test note',
        ]);
    }

    public function test_edit_page()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get('/admin/notes/1/edit');
        $response->assertViewIs('admin.notes.edit');
    }

    public function test_edit_a_note()
    {
        Queue::fake();
        $user = factory(User::class)->create();

        $this->actingAs($user)->post('/admin/notes/1', [
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
        $user = factory(User::class)->create();

        $this->actingAs($user)->post('/admin/notes/1', [
            '_method' => 'DELETE',
        ]);
        $this->assertSoftDeleted('notes', [
            'id' => '1',
        ]);
    }
}
