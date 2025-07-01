<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Bio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BioTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_bios_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/admin/bio');
        $response->assertSeeText('Edit bio');
    }

    #[Test]
    public function admin_can_create_bio(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)
            ->post('/admin/bio', [
                '_method' => 'PUT',
                'content' => 'Bio content',
            ]);
        $this->assertDatabaseHas('bios', ['content' => 'Bio content']);
    }

    #[Test]
    public function admin_can_load_existing_bio(): void
    {
        $user = User::factory()->make();
        $bio = Bio::factory()->create([
            'content' => 'This is <em>my</em> bio. It uses <strong>HTML</strong>.',
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/bio');
        $response->assertSeeText('This is <em>my</em> bio. It uses <strong>HTML</strong>.');
    }

    #[Test]
    public function admin_can_edit_bio(): void
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
