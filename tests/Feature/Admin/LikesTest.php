<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Jobs\ProcessLike;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LikesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function likesPageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/admin/likes');
        $response->assertSeeText('Likes');
    }

    #[Test]
    public function likeCreateFormLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/admin/likes/create');
        $response->assertSeeText('New Like');
    }

    #[Test]
    public function adminCanCreateLike(): void
    {
        Queue::fake();
        $user = User::factory()->make();

        $this->actingAs($user)
            ->post('/admin/likes', [
                'like_url' => 'https://example.com',
            ]);
        $this->assertDatabaseHas('likes', [
            'url' => 'https://example.com',
        ]);
        Queue::assertPushed(ProcessLike::class);
    }

    #[Test]
    public function likeEditFormLoads(): void
    {
        $user = User::factory()->make();
        $like = Like::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin/likes/' . $like->id . '/edit');
        $response->assertSee('Edit Like');
    }

    #[Test]
    public function adminCanEditLike(): void
    {
        Queue::fake();
        $user = User::factory()->make();
        $like = Like::factory()->create();

        $this->actingAs($user)
            ->post('/admin/likes/' . $like->id, [
                '_method' => 'PUT',
                'like_url' => 'https://example.com',
            ]);
        $this->assertDatabaseHas('likes', [
            'url' => 'https://example.com',
        ]);
        Queue::assertPushed(ProcessLike::class);
    }

    #[Test]
    public function adminCanDeleteLike(): void
    {
        $like = Like::factory()->create();
        $url = $like->url;
        $user = User::factory()->make();

        $this->actingAs($user)
            ->post('/admin/likes/' . $like->id, [
                '_method' => 'DELETE',
            ]);
        $this->assertDatabaseMissing('likes', [
            'url' => $url,
        ]);
    }
}
