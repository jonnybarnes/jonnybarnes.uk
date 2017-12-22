<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminTest extends TestCase
{
    public function test_admin_page_redirects_to_login()
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_login_page()
    {
        $response = $this->get('/login');
        $response->assertViewIs('login');
    }

    public function test_attempt_login_with_good_credentials()
    {
        $response = $this->post('/login', [
            'username' => config('admin.user'),
            'password' => config('admin.pass'),
        ]);
        $response->assertRedirect('/admin');
    }

    public function test_attempt_login_with_bad_credentials()
    {
        $response = $this->post('/login', [
            'username' => 'bad',
            'password' => 'credentials',
        ]);
        $response->assertRedirect('/login');
    }
}
