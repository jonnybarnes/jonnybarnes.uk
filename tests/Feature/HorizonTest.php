<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class HorizonTest extends TestCase
{
    /**
     * Horizon has its own test suite, here we just test it has been installed successfully.
     *
     * @test
     *
     * @return void
     */
    public function horizonIsInstalled(): void
    {
        $user = User::factory()->create([
            'name' => 'jonny',
        ]);

        $response = $this->actingAs($user)->get('/horizon');

        $response->assertStatus(200);
    }
}
