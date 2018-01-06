<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\MicropubClient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MicropbClientsTest extends TestCase
{
    public function test_notes_relationship()
    {
        $client = MicropubClient::find(1);
        $this->assertInstanceOf(Collection::class, $client->notes);
    }
}
