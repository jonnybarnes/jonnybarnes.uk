<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\AddClientToDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddClientToDatabaseJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function client_is_added_to_database_by_job(): void
    {
        $job = new AddClientToDatabase('https://example.org/client');
        $job->handle();
        $this->assertDatabaseHas('clients', [
            'client_url' => 'https://example.org/client',
            'client_name' => 'https://example.org/client',
        ]);
    }
}
