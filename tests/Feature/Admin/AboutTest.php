<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\About;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AboutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_about_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/admin/about');
        $response->assertSeeText('Edit About');
    }

    #[Test]
    public function admin_can_create_about(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)
            ->post('/admin/about', [
                '_method' => 'PUT',
                'content' => 'About content',
            ]);
        $this->assertDatabaseHas('about', ['content' => 'About content']);
    }

    #[Test]
    public function admin_can_load_existing_about(): void
    {
        $user = User::factory()->make();
        $about = About::factory()->create([
            'content' => 'This is <em>my</em> about page. It uses <strong>HTML</strong>.',
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/about');
        $response->assertSeeText('This is <em>my</em> about page. It uses <strong>HTML</strong>.');
    }

    #[Test]
    public function admin_can_edit_about(): void
    {
        $user = User::factory()->make();
        $about = About::factory()->create();

        $this->actingAs($user)
            ->post('/admin/about', [
                '_method' => 'PUT',
                'content' => 'This about page has been edited',
            ]);
        $this->assertDatabaseHas('about', [
            'content' => 'This about page has been edited',
        ]);
    }
}
