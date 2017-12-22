<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SessionStoreControllerTest extends TestCase
{
    public function test_colour_preference_saved()
    {
        $response = $this->post('update-colour-scheme', ['css' => 'some.css']);
        $response->assertJson(['status' => 'ok']);
    }
}
