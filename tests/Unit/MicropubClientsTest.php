<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\MicropubClient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MicropubClientsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function weCanGetNotesRelatingToClient(): void
    {
        $client = MicropubClient::factory()->make();

        $this->assertInstanceOf(Collection::class, $client->notes);
    }
}
