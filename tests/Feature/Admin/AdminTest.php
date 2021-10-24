<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminTest extends TestCase
{
    /** @test */
    public function adminPageRedirectsUnauthedUsersToLoginPage(): void
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
}
