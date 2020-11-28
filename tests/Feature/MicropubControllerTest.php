<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use Tests\TestToken;
use App\Jobs\SendWebMentions;
use App\Models\{Media, Place};
use App\Jobs\SyndicateNoteToTwitter;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MicropubControllerTest extends TestCase
{
    use DatabaseTransactions;
    use TestToken;

    /**
     * Test a GET request for the micropub endpoint without a token gives a
     * 400 response. Also check the error message.
     *
     * @return void
     */
    public function test_micropub_get_request_without_token_returns_401_response()
    {
        $response = $this->get('/api/post');
        $response->assertStatus(401);
        $response->assertJsonFragment(['error_description' => 'No access token was provided in the request']);
    }

    /**
     * Test a GET request for the micropub endpoint without a valid token gives
     * a 400 response. Also check the error message.
     *
     * @return void
     */
    public function test_micropub_get_request_without_valid_token_returns_400_response()
    {
        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer abc123']);
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token did not pass validation']);
    }

    /**
     * Test a GET request for the micropub endpoint with a valid token gives a
     * 200 response. Check token information is returned in the response.
     *
     * @return void
     */
    public function test_micropub_get_request_with_valid_token_returns_200_response()
    {
        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['response' => 'token']);
    }

    /**
     * Test a GET request for syndication targets.
     *
     * @return void
     */
    public function test_micropub_get_request_for_syndication_targets()
    {
        $response = $this->get('/api/post?q=syndicate-to', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJsonFragment(['uid' => 'https://twitter.com/jonnybarnes']);
    }

    /**
     * Test a request for places.
     *
     * @return void
     */
    public function test_micropub_get_request_for_nearby_places()
    {
        $response = $this->get('/api/post?q=geo:53.5,-2.38', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJson(['places' => [['slug' =>'the-bridgewater-pub']]]);
    }

    /**
     * Test a request for places, this time with an uncertainty parameter.
     *
     * @return void
     */
    public function test_micropub_get_request_for_nearby_places_with_uncertainty_parameter()
    {
        $response = $this->get('/api/post?q=geo:53.5,-2.38', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJson(['places' => [['slug' => 'the-bridgewater-pub']]]);
    }

    /**
     * Test a request for places, where there will be an “empty” response.
     *
     * @return void
     */
    public function test_micropub_get_request_for_nearby_places_where_non_exist()
    {
        $response = $this->get('/api/post?q=geo:1.23,4.56', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJson(['places' => []]);
    }

    /**
     * Test a request for the micropub config.
     *
     * @return void
     */
    public function test_micropub_get_request_for_config()
    {
        $response = $this->get('/api/post?q=config', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJsonFragment(['uid' => 'https://twitter.com/jonnybarnes']);
    }

    /**
     * Test a valid micropub requests creates a new note.
     *
     * @return void
     */
    public function test_micropub_post_request_creates_new_note()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => $note,
                'published' => Carbon::now()->toW3CString(),
                'location' => 'geo:1.23,4.56',
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('notes', ['note' => $note]);
    }

    /**
     * Test a valid micropub requests creates a new note and syndicates to Twitter.
     *
     * @return void
     */
    public function test_micropub_post_request_creates_new_note_sends_to_twitter()
    {
        Queue::fake();
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => $note,
                'mp-syndicate-to' => 'https://twitter.com/jonnybarnes'
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('notes', ['note' => $note]);
        Queue::assertPushed(SyndicateNoteToTwitter::class);
    }

    /**
     * Test a valid micropub requests creates a new place.
     *
     * @return void
     */
    public function test_micropub_post_request_creates_new_place()
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'card',
                'name' => 'The Barton Arms',
                'geo' => 'geo:53.4974,-2.3768'
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('places', ['slug' => 'the-barton-arms']);
    }

    /**
     * Test a valid micropub requests creates a new place with latitude
     * and longitude values defined separately.
     *
     * @return void
     */
    public function test_micropub_post_request_creates_new_place_with_latlng()
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'card',
                'name' => 'The Barton Arms',
                'latitude' => '53.4974',
                'longitude' => '-2.3768',
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('places', ['slug' => 'the-barton-arms']);
    }

    public function test_micropub_post_request_with_invalid_token_returns_expected_error_response()
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => 'A random note',
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getInvalidToken()]
        );
        $response->assertStatus(400);
        $response->assertJson(['error' => 'invalid_token']);
    }

    public function test_micropub_post_request_with_scopeless_token_returns_expected_error_response()
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => 'A random note',
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithNoScope()]
        );
        $response->assertStatus(400);
        $response->assertJson(['error_description' => 'The provided token has no scopes']);
    }

    public function test_micropub_post_request_for_place_without_create_scope_errors()
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'card',
                'name' => 'The Barton Arms',
                'geo' => 'geo:53.4974,-2.3768'
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithIncorrectScope()]
        );
        $response->assertStatus(401);
        $response->assertJson(['error' => 'insufficient_scope']);
    }

    /**
     * Test a valid micropub requests using JSON syntax creates a new note.
     *
     * @return void
     */
    public function test_micropub_post_request_with_json_syntax_creates_new_note()
    {
        Queue::fake();
        Media::create([
            'path' => 'test-photo.jpg',
            'type' => 'image',
        ]);
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                    'in-reply-to' => ['https://aaronpk.localhost'],
                    'mp-syndicate-to' => [
                        'https://twitter.com/jonnybarnes',
                    ],
                    'photo' => [config('filesystems.disks.s3.url') . '/test-photo.jpg'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
        Queue::assertPushed(SendWebMentions::class);
        Queue::assertPushed(SyndicateNoteToTwitter::class);
    }

    /**
     * Test a valid micropub requests using JSON syntax creates a new note with
     * existing self-created place.
     *
     * @return void
     */
    public function test_micropub_post_request_with_json_syntax_creates_new_note_with_existing_place_in_location()
    {
        $place = new Place();
        $place->name = 'Test Place';
        $place->latitude = 1.23;
        $place->longitude = 4.56;
        $place->save();
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                    'location' => [$place->longurl],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
    }

    /**
     * Test a valid micropub requests using JSON syntax creates a new note with
     * a new place defined in the location block.
     *
     * @return void
     */
    public function test_micropub_post_request_with_json_syntax_creates_new_note_with_new_place_in_location()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                ],
                'location' => [
                    'type' => ['h-card'],
                    'properties' => [
                        'name' => ['Awesome Venue'],
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
            'name' => 'Awesome Venue',
        ]);
    }

    /**
     * Test a valid micropub requests using JSON syntax creates a new note without
     * a new place defined in the location block if there is missing data.
     *
     * @return void
     */
    public function test_micropub_post_request_with_json_syntax_creates_new_note_without_new_place_in_location()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                    'location' => [[
                        'type' => ['h-card'],
                        'properties' => [
                            'name' => ['Awesome Venue'],
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

    /**
     * Test a micropub requests using JSON syntax without a token returns an
     * error. Also check the message.
     *
     * @return void
     */
    public function test_micropub_post_request_with_json_syntax_without_token_returns_error()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                ],
            ]
        );
        $response
            ->assertJson([
                'response' => 'error',
                'error' => 'unauthorized'
            ])
            ->assertStatus(401);
    }

    /**
     * Test a micropub requests using JSON syntax without a valid token returns
     * an error. Also check the message.
     *
     * @return void
     */
    public function test_micropub_post_request_with_json_syntax_with_insufficient_token_returns_error()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithIncorrectScope()]
        );
        $response
            ->assertJson([
                'response' => 'error',
                'error' => 'insufficient_scope'
            ])
            ->assertStatus(401);
    }

    public function test_micropub_post_request_with_json_syntax_for_unsupported_type_returns_error()
    {
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-unsopported'], // a request type I don’t support
                'properties' => [
                    'content' => ['Some content'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson([
                'response' => 'error',
                'error_description' => 'unsupported_request_type'
            ])
            ->assertStatus(500);
    }

    public function test_micropub_post_request_with_json_syntax_creates_new_place()
    {
        $faker = \Faker\Factory::create();
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-card'],
                'properties' => [
                    'name' => $faker->name,
                    'geo' => 'geo:' . $faker->latitude . ',' . $faker->longitude
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson(['response' => 'created'])
            ->assertStatus(201);
    }

    public function test_micropub_post_request_with_json_syntax_and_uncertainty_parameter_creates_new_place()
    {
        $faker = \Faker\Factory::create();
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-card'],
                'properties' => [
                    'name' => $faker->name,
                    'geo' => 'geo:' . $faker->latitude . ',' . $faker->longitude . ';u=35'
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson(['response' => 'created'])
            ->assertStatus(201);
    }

    public function test_micropub_post_request_with_json_syntax_update_replace_post()
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url') . '/notes/A',
                'replace' => [
                    'content' => ['replaced content'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertStatus(200);
    }

    public function test_micropub_post_request_with_json_syntax_update_add_post()
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url') . '/notes/A',
                'add' => [
                    'syndication' => [
                        'https://www.swarmapp.com/checkin/123',
                        'https://www.facebook.com/checkin/123',
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertStatus(200);
        $this->assertDatabaseHas('notes', [
            'swarm_url' => 'https://www.swarmapp.com/checkin/123',
            'facebook_url' => 'https://www.facebook.com/checkin/123',
        ]);
    }

    public function test_micropub_post_request_with_json_syntax_update_add_image_to_post()
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url') . '/notes/A',
                'add' => [
                    'photo' => ['https://example.org/photo.jpg'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertStatus(200);
        $this->assertDatabaseHas('media_endpoint', [
            'path' => 'https://example.org/photo.jpg',
        ]);
    }

    public function test_micropub_post_request_with_json_syntax_update_add_post_errors_for_non_note()
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url') . '/blog/A',
                'add' => [
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson(['error' => 'invalid'])
            ->assertStatus(500);
    }

    public function test_micropub_post_request_with_json_syntax_update_add_post_errors_for_note_not_found()
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url') . '/notes/ZZZZ',
                'add' => [
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson(['error' => 'invalid_request'])
            ->assertStatus(404);
    }

    public function test_micropub_post_request_with_json_syntax_update_add_post_errors_for_unsupported_request()
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url') . '/notes/A',
                'morph' => [ // or any other unsupported update type
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson(['response' => 'error'])
            ->assertStatus(500);
    }

    public function test_micropub_post_request_with_json_syntax_update_errors_for_insufficient_scope()
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url') . '/notes/B',
                'add' => [
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getTokenWithIncorrectScope()]
        );
        $response
            ->assertStatus(401)
            ->assertJson(['error' => 'insufficient_scope']);
    }

    public function test_micropub_post_request_with_json_syntax_update_replace_post_syndication()
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url') . '/notes/B',
                'replace' => [
                    'syndication' => [
                        'https://www.swarmapp.com/checkin/the-id',
                        'https://www.facebook.com/post/the-id',
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertStatus(200);
        $this->assertDatabaseHas('notes', [
            'swarm_url' => 'https://www.swarmapp.com/checkin/the-id',
            'facebook_url' => 'https://www.facebook.com/post/the-id',
        ]);
    }

    public function test_access_token_form_encoded()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => $note,
                'published' => Carbon::now()->toW3CString(),
                'access_token' => (string) $this->getToken(),
            ]
        );
        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('notes', ['note' => $note]);
    }
}
