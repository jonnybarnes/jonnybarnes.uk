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

    public function test_faked_ownyourswarm_request()
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
        $this->assertDatabaseHas('places', [
            'external_urls' => '{"foursquare": "https://foursquare.com/v/123456"}'
        ]);
        $this->assertDatabaseHas('notes', [
            'swarm_url' => 'https://www.swarmapp.com/checkin/abc'
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
    }
}
