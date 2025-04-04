<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminTest extends TestCase
{
    #[Test]
    public function adminPageRedirectsUnauthorisedUsersToLoginPage(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function loginPageLoads(): void
    {
        $response = $this->get('/login');
        $response->assertViewIs('login');
    }

    #[Test]
    public function loginAttemptWithBadCredentialsFails(): void
    {
        $response = $this->post('/login', [
            'username' => 'bad',
            'password' => 'credentials',
        ]);
        $response->assertRedirect('/login');
    }

    #[Test]
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

        $response->assertRedirect('/admin');
    }

    #[Test]
    public function whenLoggedInRedirectsToAdminPage(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/');
    }

    #[Test]
    public function loggedOutUsersSimplyRedirected(): void
    {
        $response = $this->get('/logout');
        $response->assertRedirect('/');
    }

    #[Test]
    public function loggedInUsersShownLogoutForm(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/logout');
        $response->assertViewIs('logout');
    }

    #[Test]
    public function loggedInUsersCanLogout(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect('/');
    }
}
