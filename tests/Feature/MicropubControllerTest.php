<?php

declare(strict_types=1);

namespace Tests\Feature;

use Faker\Factory;
use App\Jobs\{SendWebMentions, SyndicateNoteToTwitter};
use App\Models\{Media, Place};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\TestToken;

class MicropubControllerTest extends TestCase
{
    use DatabaseTransactions;
    use TestToken;

    /** @test */
    public function micropubGetRequestWithoutTokenReturnsErrorResponse(): void
    {
        $response = $this->get('/api/post');
        $response->assertStatus(401);
        $response->assertJsonFragment(['error_description' => 'No access token was provided in the request']);
    }

    /** @test */
    public function micropubGetRequestWithoutValidTokenReturnsErrorResponse(): void
    {
        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer abc123']);
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token did not pass validation']);
    }

    /**
     * Test a GET request for the micropub endpoint with a valid token gives a
     * 200 response. Check token information is also returned in the response.
     *
     * @test
     */
    public function micropubGetRequestWithValidTokenReturnsOkResponse(): void
    {
        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['response' => 'token']);
    }

    /** @test */
    public function micropubClientsCanRequestSyndicationTargets(): void
    {
        $response = $this->get('/api/post?q=syndicate-to', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJsonFragment(['uid' => 'https://twitter.com/jonnybarnes']);
    }

    /** @test */
    public function micropubClientsCanRequestKnownNearbyPlaces(): void
    {
        $response = $this->get('/api/post?q=geo:53.5,-2.38', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJson(['places' => [['slug' => 'the-bridgewater-pub']]]);
    }

    /**
     * @test
     * @todo Add uncertainty parameter
     */
    public function micropubClientsCanRequestKnownNearbyPlacesWithUncertaintyParameter(): void
    {
        $response = $this->get('/api/post?q=geo:53.5,-2.38', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJson(['places' => [['slug' => 'the-bridgewater-pub']]]);
    }

    /** @test */
    public function returnEmptyResultWhenMicropubClientRequestsKnownNearbyPlaces(): void
    {
        $response = $this->get('/api/post?q=geo:1.23,4.56', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJson(['places' => []]);
    }

    /** @test */
    public function micropubClientCanRequestEndpointConfig(): void
    {
        $response = $this->get('/api/post?q=config', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJsonFragment(['uid' => 'https://twitter.com/jonnybarnes']);
    }

    /** @test */
    public function micropubClientCanCreateNewNote(): void
    {
        $faker = Factory::create();
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

    /** @test */
    public function micropubClientCanRequestTheNewNoteIsSyndicatedToTwitter(): void
    {
        Queue::fake();
        $faker = Factory::create();
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

    /** @test */
    public function micropubClientsCanCreateNewPlaces(): void
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

    /** @test */
    public function micropubClientsCanCreateNewPlacesWithOldLocationSyntax(): void
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

    /** @test */
    public function micropubClientWebRequestWithInvalidTokenReturnsErrorResponse(): void
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

    /** @test */
    public function micropubClientWebRequestWithTokenWithoutAnyScopesReturnsErrorResponse(): void
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

    /** @test */
    public function micropubClientWebRequestWithTokenWithoutCreateScopesReturnsErrorResponse(): void
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
     * @test
     */
    public function micropubClientApiRequestCreatesNewNote(): void
    {
        Queue::fake();
        Media::create([
            'path' => 'test-photo.jpg',
            'type' => 'image',
        ]);
        $faker = Factory::create();
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
     * @test
     */
    public function micropubClientApiRequestCreatesNewNoteWithExistingPlaceInLocationData(): void
    {
        $place = new Place();
        $place->name = 'Test Place';
        $place->latitude = 1.23;
        $place->longitude = 4.56;
        $place->save();
        $faker = Factory::create();
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
     * @test
     */
    public function micropubClientApiRequestCreatesNewNoteWithNewPlaceInLocationData(): void
    {
        $faker = Factory::create();
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
     * @test
     */
    public function micropubClientApiRequestCreatesNewNoteWithoutNewPlaceInLocationData(): void
    {
        $faker = Factory::create();
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
     * @test
     */
    public function micropubClientApiRequestWithoutTokenReturnsError(): void
    {
        $faker = Factory::create();
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
     * @test
     */
    public function micropubClientApiRequestWithTokenWithInsufficientPermissionReturnsError(): void
    {
        $faker = Factory::create();
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

    /** @test */
    public function micropubClientApiRequestForUnsupportedPostTypeReturnsError(): void
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

    /** @test */
    public function micropubClientApiRequestCreatesNewPlace(): void
    {
        $faker = Factory::create();
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

    /** @test */
    public function micropubClientApiRequestCreatesNewPlaceWithUncertaintyParameter(): void
    {
        $faker = Factory::create();
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

    /** @test */
    public function micropubClientApiRequestUpdatesExistingNote(): void
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

    /** @test */
    public function micropubClientApiRequestUpdatesNoteSyndicationLinks(): void
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

    /** @test */
    public function micropubClientApiRequestAddsImageToNote(): void
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

    /** @test */
    public function micropubClientApiRequestReturnsErrorTryingToUpdateNonNoteModel(): void
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

    /** @test */
    public function micropubClientApiRequestReturnsErrorTryingToUpdateNonExistingNote(): void
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

    /** @test */
    public function micropubClientApiRequestReturnsErrorWhenTryingToUpdateUnsupportedProperty(): void
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

    /** @test */
    public function micropubClientApiRequestWithTokenWithInsufficientScopeReturnsError(): void
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

    /** @test */
    public function micropubClientApiRequestCanReplaceNoteSyndicationTargets(): void
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

    /** @test */
    public function micropubClientWebReauestCanEncodeTokenWithinTheForm(): void
    {
        $faker = Factory::create();
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
