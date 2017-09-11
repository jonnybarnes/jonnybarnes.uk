<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityStreamTest extends TestCase
{
    /**
     * Test request to homepage returns data for site owner.
     *
     * @return void
     */
    public function test_homepage_returns_data_for_site_owner()
    {
        $response = $this->get('/', ['Accept' => 'application/activity+json']);
        $response->assertHeader('Content-Type', 'application/activity+json');
        $response->assertJson([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => config('app.url'),
            'type' => 'Person',
            'name' => config('app.display_name'),
        ]);
    }
}
