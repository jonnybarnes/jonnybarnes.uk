<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\AddClientToDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddClientToDatabaseJobTest extends TestCase
{
    use DatabaseTransactions;

    public function test_job_adds_client()
    {
        $job = new AddClientToDatabase('https://example.org/client');
        $job->handle();
        $this->assertDatabaseHas('clients', [
            'client_url' => 'https://example.org/client',
            'client_name' => 'https://example.org/client',
        ]);
    }
}
