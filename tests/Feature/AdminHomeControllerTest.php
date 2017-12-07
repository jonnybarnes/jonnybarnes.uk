<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminHomeControllerTest extends TestCase
{
    public function test_admin_homepage()
    {
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin');
        $response->assertViewIs('admin.welcome');
    }
}
