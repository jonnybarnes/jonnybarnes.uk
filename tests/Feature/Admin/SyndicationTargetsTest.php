<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\SyndicationTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyndicationTargetsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->get('/admin/syndication');
        $response->assertRedirect();
    }

    #[Test]
    public function index_lists_syndication_targets(): void
    {
        $user = User::factory()->make();
        $target = SyndicationTarget::factory()->create();

        $response = $this->actingAs($user)->get('/admin/syndication');
        $response->assertOk();
        $response->assertSeeText($target->uid);
    }

    #[Test]
    public function create_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/syndication/create');
        $response->assertOk();
    }

    #[Test]
    public function store_creates_a_new_syndication_target(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)->post('/admin/syndication', [
            'uid' => 'https://mastodon.social/users/me',
            'name' => 'Mastodon',
        ]);

        $this->assertDatabaseHas('syndication_targets', [
            'uid' => 'https://mastodon.social/users/me',
            'name' => 'Mastodon',
        ]);
    }

    #[Test]
    public function store_redirects_to_index_after_creation(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->post('/admin/syndication', [
            'uid' => 'https://mastodon.social/users/me',
            'name' => 'Mastodon',
        ]);

        $response->assertRedirect('/admin/syndication');
    }

    #[Test]
    public function edit_page_loads_with_target_data(): void
    {
        $user = User::factory()->make();
        $target = SyndicationTarget::factory()->create(['name' => 'My Mastodon']);

        $response = $this->actingAs($user)->get("/admin/syndication/{$target->id}/edit");
        $response->assertOk();
        $response->assertSee('value="My Mastodon"', false);
    }

    #[Test]
    public function update_modifies_syndication_target(): void
    {
        $user = User::factory()->make();
        $target = SyndicationTarget::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)->put("/admin/syndication/{$target->id}", [
            'uid' => $target->uid,
            'name' => 'New Name',
        ]);

        $this->assertDatabaseHas('syndication_targets', [
            'id' => $target->id,
            'name' => 'New Name',
        ]);
    }

    #[Test]
    public function update_redirects_to_index(): void
    {
        $user = User::factory()->make();
        $target = SyndicationTarget::factory()->create();

        $response = $this->actingAs($user)->put("/admin/syndication/{$target->id}", [
            'uid' => $target->uid,
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect('/admin/syndication');
    }

    #[Test]
    public function destroy_deletes_syndication_target(): void
    {
        $user = User::factory()->make();
        $target = SyndicationTarget::factory()->create();

        $this->actingAs($user)->delete("/admin/syndication/{$target->id}");

        $this->assertDatabaseMissing('syndication_targets', ['id' => $target->id]);
    }

    #[Test]
    public function destroy_redirects_to_index(): void
    {
        $user = User::factory()->make();
        $target = SyndicationTarget::factory()->create();

        $response = $this->actingAs($user)->delete("/admin/syndication/{$target->id}");

        $response->assertRedirect('/admin/syndication');
    }
}
