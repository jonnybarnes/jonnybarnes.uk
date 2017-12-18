<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminHomeControllerTest extends TestCase
{
    public function test_admin_homepage()
    {
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin');
        $response->assertViewIs('admin.welcome');
    }
}
