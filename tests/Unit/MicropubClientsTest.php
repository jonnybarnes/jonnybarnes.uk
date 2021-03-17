<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\MicropubClient;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class MicropubClientsTest extends TestCase
{
    /** @test */
    public function weCanGetNotesRelatingToClient(): void
    {
        $client = MicropubClient::find(1);
        $this->assertInstanceOf(Collection::class, $client->notes);
    }
}
