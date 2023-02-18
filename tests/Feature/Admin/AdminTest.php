<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\TestCase;

class AdminTest extends TestCase
{
    /** @test */
    public function adminPageRedirectsUnauthorisedUsersToLoginPage(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function loginPageLoads(): void
    {
        $response = $this->get('/login');
        $response->assertViewIs('login');
    }

    /** @test */
    public function loginAttemptWithBadCredentialsFails(): void
    {
        $response = $this->post('/login', [
            'username' => 'bad',
            'password' => 'credentials',
        ]);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function loginSucceeds(): void
    {
        User::factory([
            'name' => 'admin',
            'password' => bcrypt('password'),
        ])->create();

        $response = $this->post('/login', [
            'name' => 'admin',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
    }

    /** @test */
    public function whenLoggedInRedirectsToAdminPage(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/');
    }

    /** @test */
    public function loggedOutUsersSimplyRedirected(): void
    {
        $response = $this->get('/logout');
        $response->assertRedirect('/');
    }

    /** @test */
    public function loggedInUsersShownLogoutForm(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/logout');
        $response->assertViewIs('logout');
    }

    /** @test */
    public function loggedInUsersCanLogout(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect('/');
    }
}
