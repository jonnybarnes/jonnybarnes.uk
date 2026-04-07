<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Jobs\SendWebMentions;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function notes_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/notes');
        $response->assertViewIs('admin.notes.index');
    }

    #[Test]
    public function note_create_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/notes/create');
        $response->assertViewIs('admin.notes.create');
    }

    #[Test]
    public function admin_can_create_new_note(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)->post('/admin/notes', [
            'content' => 'A new test note',
        ]);

        $this->assertDatabaseHas('notes', [
            'note' => 'A new test note',
        ]);
    }

    #[Test]
    public function note_edit_form_loads(): void
    {
        $user = User::factory()->make();
        $note = Note::factory()->create();

        $response = $this->actingAs($user)->get('/admin/notes/'.$note->id.'/edit');
        $response->assertViewIs('admin.notes.edit');
    }

    #[Test]
    public function admin_can_edit_note(): void
    {
        Queue::fake();
        $user = User::factory()->make();
        $note = Note::factory()->create();

        $this->actingAs($user)->post('/admin/notes/'.$note->id, [
            '_method' => 'PUT',
            'content' => 'An edited note',
            'webmentions' => true,
        ]);

        $this->assertDatabaseHas('notes', [
            'note' => 'An edited note',
        ]);
        Queue::assertPushed(SendWebMentions::class);
    }

    #[Test]
    public function admin_can_delete_note(): void
    {
        $user = User::factory()->make();
        $note = Note::factory()->create();

        $this->actingAs($user)->post('/admin/notes/'.$note->id, [
            '_method' => 'DELETE',
        ]);
        $this->assertSoftDeleted('notes', [
            'id' => $note->id,
        ]);
    }
}
