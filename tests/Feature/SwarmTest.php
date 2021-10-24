<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendWebMentions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\TestToken;

class SwarmTest extends TestCase
{
    use RefreshDatabase;
    use TestToken;

    /**
     * Given a check in to Foursquare, this is the content Ownyourswarm will post to us.
     *
     * @test
     */
    public function mockedOwnyourswarmRequestWithFoursquare(): void
    {
        Queue::fake();

        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [Carbon::now()->toDateTimeString()],
                    'syndication' => ['https://www.swarmapp.com/checkin/abc'],
                    'content' => [[
                        'value' => 'My first #checkin using Example Product',
                        'html' => 'My first #checkin using <a href="http://example.org">Example Product</a>',
                    ]],
                ],
                'checkin' => [
                    'type' => ['h-card'],
                    'properties' => [
                        'name' => ['Awesome Venue'],
                        'url' => ['https://foursquare.com/v/123456'],
                        'latitude' => ['1.23'],
                        'longitude' => ['4.56'],
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('notes', [
            'swarm_url' => 'https://www.swarmapp.com/checkin/abc'
        ]);
        $this->assertDatabaseHas('places', [
            'external_urls' => '{"foursquare": "https://foursquare.com/v/123456"}'
        ]);

        Queue::assertPushed(SendWebMentions::class);
    }

    /**
     * This request would actually come from another client than OwnYourSwarm, but we’re testing
     * OpenStreetMap data.
     *
     * @test
     */
    public function mockedOwnyourswarmRequestWithOsm(): void
    {
        Queue::fake();

        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [Carbon::now()->toDateTimeString()],
                    'content' => [[
                        'value' => 'My first #checkin using Example Product',
                        'html' => 'My first #checkin using <a href="http://example.org">Example Product</a>',
                    ]],
                ],
                'checkin' => [
                    'type' => ['h-card'],
                    'properties' => [
                        'name' => ['Awesome Venue'],
                        'url' => ['https://www.openstreetmap.org/way/123456'],
                        'latitude' => ['1.23'],
                        'longitude' => ['4.56'],
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('places', [
            'external_urls' => '{"osm": "https://www.openstreetmap.org/way/123456"}'
        ]);

        Queue::assertPushed(SendWebMentions::class);
    }

    /**
     * This request would actually come from another client than OwnYourSwarm, as that would include a Foursquare URL
     *
     * @test
     */
    public function mockedOwnyourswarmRequestWithoutKnownExternalUrl(): void
    {
        Queue::fake();

        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [Carbon::now()->toDateTimeString()],
                    'content' => [[
                        'value' => 'My first #checkin using Example Product',
                        'html' => 'My first #checkin using <a href="http://example.org">Example Product</a>',
                    ]],
                ],
                'checkin' => [
                    'type' => ['h-card'],
                    'properties' => [
                        'name' => ['Awesome Venue'],
                        'url' => ['https://www.example.org/way/123456'],
                        'latitude' => ['1.23'],
                        'longitude' => ['4.56'],
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('places', [
            'external_urls' => '{"default": "https://www.example.org/way/123456"}'
        ]);

        Queue::assertPushed(SendWebMentions::class);
    }

    /** @test */
    public function mockedOwnyourswarmRequestWithNoTextContent(): void
    {
        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [Carbon::now()->toDateTimeString()],
                    'syndication' => ['https://www.swarmapp.com/checkin/def'],
                ],
                'checkin' => [
                    'type' => ['h-card'],
                    'properties' => [
                        'name' => ['Awesomer Venue'],
                        'url' => ['https://foursquare.com/v/654321'],
                        'latitude' => ['3.21'],
                        'longitude' => ['6.54'],
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('places', [
            'external_urls' => '{"foursquare": "https://foursquare.com/v/654321"}'
        ]);
        $this->assertDatabaseHas('notes', [
            'swarm_url' => 'https://www.swarmapp.com/checkin/def'
        ]);
        // Check the default text content for the note was saved
        $this->get($response->__get('headers')->get('location'))->assertSee('📍');
    }

    /** @test */
    public function mockedOwnyourswarmRequestSavesJustThePostWhenAnErrorOccursInTheCheckinData(): void
    {
        Queue::fake();

        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [Carbon::now()->toDateTimeString()],
                    'syndication' => ['https://www.swarmapp.com/checkin/abc'],
                    'content' => [[
                        'value' => 'My first #checkin using Example Product',
                        'html' => 'My first #checkin using <a href="http://example.org">Example Product</a>',
                    ]],
                ],
                'checkin' => [
                    'type' => ['h-card'],
                    'properties' => [
                        'name' => ['Awesome Venue'],
                        'url' => ['https://foursquare.com/v/123456'],
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
        $this->assertDatabaseMissing('places', [
            'name' => 'Awesome Venue',
        ]);

        Queue::assertPushed(SendWebMentions::class);
    }

    /** @test */
    public function mockedOwnyourswarmRequestWithHAdrLocation(): void
    {
        Queue::fake();

        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [Carbon::now()->toDateTimeString()],
                    'syndication' => ['https://www.swarmapp.com/checkin/abc'],
                    'content' => [[
                        'value' => 'My first #checkin using Example Product',
                        'html' => 'My first #checkin using <a href="http://example.org">Example Product</a>',
                    ]],
                ],
                'location' => [
                    'type' => ['h-adr'],
                    'properties' => [
                        'latitude' => ['1.23'],
                        'longitude' => ['4.56'],
                        'street-address' => ['Awesome Street'],
                    ],
                ],
                'checkin' => [
                    'type' => ['h-card'],
                    'properties' => [
                        'name' => ['Awesome Venue'],
                        'url' => ['https://foursquare.com/v/123456'],
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
        $this->assertDatabaseMissing('places', [
            'name' => 'Awesome Venue',
        ]);

        Queue::assertPushed(SendWebMentions::class);
    }

    /** @test */
    public function ownyourswarmCheckinTestUsingRealData(): void
    {
        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [Carbon::now()->toDateTimeString()]
                ],
                'syndication' => [
                    'https://www.swarmapp.com/user/199841/checkin/5c4b1ac56dcf04002c0a4f58'
                ],
                'checkin' => [
                    'type' => ['h-card'],
                    'properties' => [
                        'name' => ['Forbidden Planet'],
                        'url' => ['https://foursquare.com/v/4ade0e46f964a520bf6f21e3'],
                        'latitude' => [53.483153021713],
                        'longitude' => [-2.2350297792539],
                        'street-address' => ['65 Oldham St.'],
                        'locality' => ['Manchester'],
                        'country-name' => ['United Kingdom'],
                        'postal-code' => ['M1 1JR']
                    ],
                    'value' => 'https://foursquare.com/v/4ade0e46f964a520bf6f21e3'
                ],
                'location' => [
                    'type' => ['h-adr'],
                    'properties' => [
                        'latitude' => [53.483153021713],
                        'longitude' => [-2.2350297792539],
                        'street-address' => ['65 Oldham St.'],
                        'locality' => ['Manchester'],
                        'country-name' => ['United Kingdom'],
                        'postal-code' => ['M1 1JR']
                    ]
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $this->assertDatabaseHas('places', [
            'name' => 'Forbidden Planet',
        ]);
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
    }
}
