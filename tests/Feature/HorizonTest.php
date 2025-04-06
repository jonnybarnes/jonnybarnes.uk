<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HorizonTest extends TestCase
{
    /**
     * Horizon has its own test suite, here we just test it has been installed successfully.
     */
    #[Test]
    public function horizon_is_installed(): void
    {
        $user = User::factory()->create([
            'name' => 'jonny',
        ]);

        $response = $this->actingAs($user)->get('/horizon');

        $response->assertStatus(200);
    }
}
