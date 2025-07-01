<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminTest extends TestCase
{
    #[Test]
    public function admin_page_redirects_unauthorised_users_to_login_page(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertViewIs('login');
    }

    #[Test]
    public function login_attempt_with_bad_credentials_fails(): void
    {
        $response = $this->post('/login', [
            'username' => 'bad',
            'password' => 'credentials',
        ]);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function login_succeeds(): void
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
    public function when_logged_in_redirects_to_admin_page(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/');
    }

    #[Test]
    public function logged_out_users_simply_redirected(): void
    {
        $response = $this->get('/logout');
        $response->assertRedirect('/');
    }

    #[Test]
    public function logged_in_users_shown_logout_form(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/logout');
        $response->assertViewIs('logout');
    }

    #[Test]
    public function logged_in_users_can_logout(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect('/');
    }
}
