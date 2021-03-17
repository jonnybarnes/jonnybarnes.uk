<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Jobs\SendWebMentions;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function notesPageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/notes');
        $response->assertViewIs('admin.notes.index');
    }

    /** @test */
    public function noteCreatePageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/notes/create');
        $response->assertViewIs('admin.notes.create');
    }

    /** @test */
    public function adminCanCreateNewNote(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)->post('/admin/notes', [
            'content' => 'A new test note',
        ]);
        $this->assertDatabaseHas('notes', [
            'note' => 'A new test note',
        ]);
    }

    /** @test */
    public function noteEditFormLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/notes/1/edit');
        $response->assertViewIs('admin.notes.edit');
    }

    /** @test */
    public function adminCanEditNote(): void
    {
        Queue::fake();
        $user = User::factory()->make();

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

    /** @test */
    public function adminCanDeleteNote(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)->post('/admin/notes/1', [
            '_method' => 'DELETE',
        ]);
        $this->assertSoftDeleted('notes', [
            'id' => '1',
        ]);
    }
}
