<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Bio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BioTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function adminBiosPageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/admin/bio');
        $response->assertSeeText('Edit bio');
    }

    /** @test */
    public function adminCanCreateBio(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)
            ->post('/admin/bio', [
                '_method' => 'PUT',
                'content' => 'Bio content',
            ]);
        $this->assertDatabaseHas('bios', ['content' => 'Bio content']);
    }

    /** @test */
    public function adminCanLoadExistingBio(): void
    {
        $user = User::factory()->make();
        $bio = Bio::factory()->create([
            'content' => 'This is <em>my</em> bio. It uses <strong>HTML</strong>.',
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/bio');
        $response->assertSeeText('This is <em>my</em> bio. It uses <strong>HTML</strong>.');
    }

    /** @test */
    public function adminCanEditBio(): void
    {
        $user = User::factory()->make();
        $bio = Bio::factory()->create();

        $this->actingAs($user)
            ->post('/admin/bio', [
                '_method' => 'PUT',
                'content' => 'This bio has been edited',
            ]);
        $this->assertDatabaseHas('bios', [
            'content' => 'This bio has been edited',
        ]);
    }
}
