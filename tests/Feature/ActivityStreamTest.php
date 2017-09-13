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
            'name' => config('user.displayname'),
        ]);
    }

    /**
     * Test request to a single note returns AS2.0 data.
     *
     * @return void
     */
    public function test_single_note_returns_as_data()
    {
        $note = \App\Note::find(11);
        $response = $this->get('/notes/B', ['Accept' => 'application/activity+json']);
        $response->assertHeader('Content-Type', 'application/activity+json');
        $response->assertJson([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Add',
            'actor' => [
                'type' => 'Person',
                'id' => config('app.url'),
            ],
            'object' => [
                'type' => 'Note',
                'name' => strip_tags($note->note)
            ]
        ]);
    }
}
