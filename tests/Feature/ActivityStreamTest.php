<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Note;
use Tests\TestCase;

class ActivityStreamTest extends TestCase
{
    /** @test */
    public function homepageRequestReturnsDataForSiteOwner(): void
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

    /** @test */
    public function requestForNoteIncludesActivityStreamData(): void
    {
        $note = Note::find(11);
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
