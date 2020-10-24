<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AdminHomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_homepage()
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
                         ->get('/admin');

        $response->assertViewIs('admin.welcome');
    }
}
