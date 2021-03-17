<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminHomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function adminHomepageLoads(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
                         ->get('/admin');

        $response->assertViewIs('admin.welcome');
    }
}
