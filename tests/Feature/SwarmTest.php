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
    }

    public function test_ownyourswarm_request_with_hadr_location()
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
    }

    /** @test */
    public function a_real_ownyourswarm_checkin()
    {
        $response = $this->json(
            'POST',
            'api/post',
            [
                'type' => ['h-entry'],
                'properties' =>[
                    'published' => [\Carbon\Carbon::now()->toDateTimeString()]
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
