<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_settings_page_loads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/admin/settings');
        $response->assertSeeText('Site settings');
    }

    #[Test]
    public function admin_can_enable_winter_effect(): void
    {
        $user = User::factory()->make();

        $this->actingAs($user)
            ->post('/admin/settings', [
                '_method' => 'PUT',
                'winter_effect_enabled' => '1',
            ]);
        $this->assertDatabaseHas('settings', ['winter_effect_enabled' => true]);
    }

    #[Test]
    public function admin_can_disable_winter_effect(): void
    {
        $user = User::factory()->make();
        Setting::factory()->create(['winter_effect_enabled' => true]);

        $this->actingAs($user)
            ->post('/admin/settings', [
                '_method' => 'PUT',
            ]);
        $this->assertDatabaseHas('settings', ['winter_effect_enabled' => false]);
    }

    #[Test]
    public function winter_effect_markup_is_hidden_when_disabled(): void
    {
        $response = $this->get('/');
        $response->assertDontSee('snow-fall');
    }

    #[Test]
    public function winter_effect_markup_is_shown_when_enabled(): void
    {
        Setting::factory()->create(['winter_effect_enabled' => true]);

        $response = $this->get('/');
        $response->assertSee('snow-fall', false);
    }
}
