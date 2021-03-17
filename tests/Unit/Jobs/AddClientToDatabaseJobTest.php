<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\AddClientToDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AddClientToDatabaseJobTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function clientIsAddedToDatabaseByJob(): void
    {
        $job = new AddClientToDatabase('https://example.org/client');
        $job->handle();
        $this->assertDatabaseHas('clients', [
            'client_url' => 'https://example.org/client',
            'client_name' => 'https://example.org/client',
        ]);
    }
}
