<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\TestToken;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SwarmTest extends TestCase
{
    use DatabaseTransactions, TestToken;

    public function test_faked_ownyourswarm_request_with_foursquare()
    {
        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [\Carbon\Carbon::now()->toDateTimeString()],
                    'syndication' => ['https://www.swarmapp.com/checkin/abc'],
                    'content' => [[
                        'value' => 'My first #checkin using Example Product',
                        'html' => 'My first #checkin using <a href="http://example.org">Example Product</a>',
                    ]],
                    'checkin' => [[
                        'type' => ['h-card'],
                        'properties' => [
                            'name' => ['Awesome Venue'],
                            'url' => ['https://foursquare.com/v/123456'],
                            'latitude' => ['1.23'],
                            'longitude' => ['4.56'],
                        ],
                    ]],
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
    }

    // this request would actually come from another client than OwnYourSwarm
    public function test_faked_ownyourswarm_request_with_osm()
    {
        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [\Carbon\Carbon::now()->toDateTimeString()],
                    'content' => [[
                        'value' => 'My first #checkin using Example Product',
                        'html' => 'My first #checkin using <a href="http://example.org">Example Product</a>',
                    ]],
                    'checkin' => [[
                        'type' => ['h-card'],
                        'properties' => [
                            'name' => ['Awesome Venue'],
                            'url' => ['https://www.openstreetmap.org/way/123456'],
                            'latitude' => ['1.23'],
                            'longitude' => ['4.56'],
                        ],
                    ]],
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
    }

    // this request would actually come from another client than OwnYourSwarm
    public function test_faked_ownyourswarm_request_without_known_external_url()
    {
        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [\Carbon\Carbon::now()->toDateTimeString()],
                    'content' => [[
                        'value' => 'My first #checkin using Example Product',
                        'html' => 'My first #checkin using <a href="http://example.org">Example Product</a>',
                    ]],
                    'checkin' => [[
                        'type' => ['h-card'],
                        'properties' => [
                            'name' => ['Awesome Venue'],
                            'url' => ['https://www.example.org/way/123456'],
                            'latitude' => ['1.23'],
                            'longitude' => ['4.56'],
                        ],
                    ]],
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
    }

    public function test_faked_ownyourswarm_request_with_no_text_content()
    {
        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [\Carbon\Carbon::now()->toDateTimeString()],
                    'syndication' => ['https://www.swarmapp.com/checkin/def'],
                    'checkin' => [[
                        'type' => ['h-card'],
                        'properties' => [
                            'name' => ['Awesomer Venue'],
                            'url' => ['https://foursquare.com/v/654321'],
                            'latitude' => ['3.21'],
                            'longitude' => ['6.54'],
                        ],
                    ]],
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
        $this->get($response->__get('headers')->get('location'))->assertSee('round pushpin');
    }

    public function test_faked_ownyourswarm_request_saves_just_post_when_error_in_checkin_data()
    {
        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'published' => [\Carbon\Carbon::now()->toDateTimeString()],
                    'syndication' => ['https://www.swarmapp.com/checkin/abc'],
                    'content' => [[
                        'value' => 'My first #checkin using Example Product',
                        'html' => 'My first #checkin using <a href="http://example.org">Example Product</a>',
                    ]],
                    'checkin' => [[
                        'type' => ['h-card'],
                        'properties' => [
                            'name' => ['Awesome Venue'],
                            'url' => ['https://foursquare.com/v/123456'],
                        ],
                    ]],
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
    }
}
