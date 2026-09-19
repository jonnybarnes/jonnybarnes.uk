<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\MicropubHandlerException;
use App\Jobs\SendWebMentions;
use App\Jobs\SyndicateNoteToBluesky;
use App\Jobs\SyndicateNoteToMastodon;
use App\Models\Media;
use App\Models\Note;
use App\Models\Place;
use App\Models\SyndicationTarget;
use Faker\Factory;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\TestToken;

class MicropubControllerTest extends TestCase
{
    use RefreshDatabase;
    use TestToken;

    #[Test]
    public function micropub_get_request_without_token_returns_error_response(): void
    {
        $response = $this->get('/api/post');
        $response->assertStatus(401);
        $response->assertJsonFragment(['error_description' => 'No access token was provided in the request']);
    }

    #[Test]
    public function micropub_get_request_without_valid_token_returns_error_response(): void
    {
        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer abc123']);
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token did not pass validation']);
    }

    /**
     * Test a GET request for the micropub endpoint with a valid token gives a
     * 200 response. Check token information is also returned in the response.
     */
    #[Test]
    public function micropub_get_request_with_valid_token_returns_ok_response(): void
    {
        $response = $this->get('/api/post', ['HTTP_Authorization' => 'Bearer '.$this->getToken()]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['response' => 'token']);
    }

    #[Test]
    public function micropub_clients_can_request_syndication_targets_can_be_empty(): void
    {
        $response = $this->get('/api/post?q=syndicate-to', ['HTTP_Authorization' => 'Bearer '.$this->getToken()]);
        $response->assertJsonFragment(['syndicate-to' => []]);
    }

    #[Test]
    public function micropub_clients_can_request_syndication_targets_populates_from_model(): void
    {
        $syndicationTarget = SyndicationTarget::factory()->create();
        $response = $this->get('/api/post?q=syndicate-to', ['HTTP_Authorization' => 'Bearer '.$this->getToken()]);
        $response->assertJsonFragment(['uid' => $syndicationTarget->uid]);
    }

    #[Test]
    public function micropub_clients_can_request_known_nearby_places(): void
    {
        Place::factory()->create([
            'name' => 'The Bridgewater Pub',
            'latitude' => '53.5',
            'longitude' => '-2.38',
        ]);
        $response = $this->get('/api/post?q=geo:53.5,-2.38', ['HTTP_Authorization' => 'Bearer '.$this->getToken()]);
        $response->assertJson(['places' => [['slug' => 'the-bridgewater-pub']]]);
    }

    /**
     * @todo Add uncertainty parameter
     *
    public function micropubClientsCanRequestKnownNearbyPlacesWithUncertaintyParameter(): void
    {
        $response = $this->get('/api/post?q=geo:53.5,-2.38', ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJson(['places' => [['slug' => 'the-bridgewater-pub']]]);
    }*/
    #[Test]
    public function return_empty_result_when_micropub_client_requests_known_nearby_places(): void
    {
        $response = $this->get('/api/post?q=geo:1.23,4.56', ['HTTP_Authorization' => 'Bearer '.$this->getToken()]);
        $response->assertJson(['places' => []]);
    }

    #[Test]
    public function micropub_client_can_request_endpoint_config(): void
    {
        $response = $this->get('/api/post?q=config', ['HTTP_Authorization' => 'Bearer '.$this->getToken()]);
        $response->assertJsonFragment(['media-endpoint' => route('media-endpoint')]);
    }

    #[Test]
    public function micropub_client_can_create_new_note(): void
    {
        $faker = Factory::create();
        $note = $faker->text;

        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => $note,
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('notes', ['note' => $note]);
    }

    #[Test]
    public function micropub_client_can_request_the_new_note_is_syndicated_to_mastodon_and_bluesky(): void
    {
        Queue::fake();

        SyndicationTarget::factory()->create([
            'uid' => 'https://mastodon.social/@jonnybarnes',
            'service_name' => 'Mastodon',
        ]);
        SyndicationTarget::factory()->create([
            'uid' => 'https://bsky.app/profile/jonnybarnes.uk',
            'service_name' => 'Bluesky',
        ]);

        $faker = Factory::create();
        $note = $faker->text;
        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => $note,
                'mp-syndicate-to' => [
                    'https://mastodon.social/@jonnybarnes',
                    'https://bsky.app/profile/jonnybarnes.uk',
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('notes', ['note' => $note]);
        Queue::assertPushed(SyndicateNoteToMastodon::class);
        Queue::assertPushed(SyndicateNoteToBluesky::class);
    }

    #[Test]
    public function micropub_clients_can_create_new_places(): void
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'card',
                'name' => 'The Barton Arms',
                'geo' => 'geo:53.4974,-2.3768',
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('places', ['slug' => 'the-barton-arms']);
    }

    #[Test]
    public function micropub_clients_can_create_new_places_with_old_location_syntax(): void
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'card',
                'name' => 'The Barton Arms',
                'latitude' => '53.4974',
                'longitude' => '-2.3768',
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('places', ['slug' => 'the-barton-arms']);
    }

    #[Test]
    public function micropub_client_web_request_with_invalid_token_returns_error_response(): void
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => 'A random note',
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getInvalidToken()]
        );
        $response->assertStatus(400);
        $response->assertJson(['error' => 'invalid_token']);
    }

    #[Test]
    public function micropub_client_web_request_with_token_without_any_scopes_returns_error_response(): void
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => 'A random note',
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getTokenWithNoScope()]
        );
        $response->assertStatus(400);
        $response->assertJson(['error_description' => 'The provided token has no scopes']);
    }

    #[Test]
    public function micropub_client_web_request_with_token_without_create_scopes_returns_error_response(): void
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => 'A random note',
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getTokenWithIncorrectScope()]
        );
        $response->assertStatus(401);
        $response->assertJson(['error' => 'insufficient_scope']);
    }

    /**
     * Test a valid micropub requests using JSON syntax creates a new note.
     */
    #[Test]
    public function micropub_client_api_request_creates_new_note_with_photo(): void
    {
        Queue::fake();
        Media::create([
            'path' => 'media/test-photo.jpg',
            'type' => 'image',
        ]);
        SyndicationTarget::factory()->create([
            'uid' => 'https://mastodon.social/@jonnybarnes',
            'service_name' => 'Mastodon',
        ]);
        SyndicationTarget::factory()->create([
            'uid' => 'https://bsky.app/profile/jonnybarnes.uk',
            'service_name' => 'Bluesky',
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
                        'https://mastodon.social/@jonnybarnes',
                        'https://bsky.app/profile/jonnybarnes.uk',
                    ],
                    'photo' => [config('filesystems.disks.public.url').'/media/test-photo.jpg'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
        Queue::assertPushed(SendWebMentions::class);
        Queue::assertPushed(SyndicateNoteToMastodon::class);
        Queue::assertPushed(SyndicateNoteToBluesky::class);
    }

    #[Test]
    public function micropub_client_api_request_creates_new_note_with_geo_location(): void
    {
        $faker = Factory::create();
        $note = $faker->text;
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                    'location' => ['geo:1.23,4.56'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('notes', [
            'note' => $note,
            'location' => '1.23, 4.56',
        ]);
    }

    /**
     * Test a valid micropub requests using JSON syntax creates a new note with
     * existing self-created place.
     */
    #[Test]
    public function micropub_client_api_request_creates_new_note_with_existing_place_in_location_data(): void
    {
        $place = new Place;
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
                    'location' => [$place->uri],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
    }

    /**
     * Test a valid micropub requests using JSON syntax creates a new note with
     * a new place defined in the location block.
     */
    #[Test]
    public function micropub_client_api_request_creates_new_note_with_new_place_in_location_data(): void
    {
        $faker = Factory::create();
        $note = $faker->text;
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                    'location' => [
                        'type' => ['h-card'],
                        'properties' => [
                            'name' => ['Awesome Venue'],
                            'latitude' => ['1.23'],
                            'longitude' => ['4.56'],
                        ],
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
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
     */
    #[Test]
    public function micropub_client_api_request_creates_new_note_without_new_place_in_location_data(): void
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
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
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
     */
    #[Test]
    public function micropub_client_api_request_without_token_returns_error(): void
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
                'error' => 'unauthorized',
            ])
            ->assertStatus(401);
    }

    /**
     * Test a micropub requests using JSON syntax without a valid token returns
     * an error. Also check the message.
     */
    #[Test]
    public function micropub_client_api_request_with_token_with_insufficient_permission_returns_error(): void
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
            ['HTTP_Authorization' => 'Bearer '.$this->getTokenWithIncorrectScope()]
        );
        $response
            ->assertJson([
                'error' => 'insufficient_scope',
                'error_description' => 'The token does not have the required scope for this request',
            ])
            ->assertStatus(401);
    }

    #[Test]
    public function micropub_client_api_request_for_unsupported_post_type_returns_error(): void
    {
        $this->mock(ExceptionHandler::class)
            ->shouldReceive('report')
            ->once()
            ->with(\Mockery::type(MicropubHandlerException::class));

        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-unsupported'], // a request type I don’t support
                'properties' => [
                    'content' => ['Some content'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson([
                'error' => 'unsupported_operation',
                'error_description' => 'The request could not be processed by this server',
            ])
            ->assertStatus(500);
    }

    #[Test]
    public function micropub_client_api_request_creates_new_place(): void
    {
        $faker = Factory::create();
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-card'],
                'properties' => [
                    'name' => [$faker->name],
                    'geo' => ['geo:'.$faker->latitude.','.$faker->longitude],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'created'])
            ->assertStatus(201);
    }

    #[Test]
    public function micropub_client_api_request_creates_new_place_with_uncertainty_parameter(): void
    {
        $faker = Factory::create();
        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-card'],
                'properties' => [
                    'name' => [$faker->name],
                    'geo' => ['geo:'.$faker->latitude.','.$faker->longitude.';u=35'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'created'])
            ->assertStatus(201);
    }

    #[Test]
    public function micropub_client_api_request_updates_existing_note(): void
    {
        $note = Note::factory()->create();
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'replace' => [
                    'content' => ['replaced content'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertSuccessful();

        $note->refresh();
        $this->assertSame('replaced content', $note->content);
    }

    #[Test]
    public function micropub_client_api_request_updates_existing_note_with_rich_text_content(): void
    {
        $note = Note::factory()->create();
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'replace' => [
                    'content' => [['value' => 'plain text', 'html' => 'html version']],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertSuccessful();

        $note->refresh();
        $this->assertSame('html version', $note->content);
    }

    #[Test]
    public function micropub_client_api_request_updates_note_syndication_links(): void
    {
        $note = Note::factory()->create();
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'add' => [
                    'syndication' => [
                        'https://www.swarmapp.com/checkin/123',
                        'https://www.facebook.com/checkin/123',
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertSuccessful();

        $this->assertDatabaseHas('notes', [
            'swarm_url' => 'https://www.swarmapp.com/checkin/123',
            'facebook_url' => 'https://www.facebook.com/checkin/123',
        ]);
    }

    #[Test]
    public function micropub_client_api_request_adds_image_to_note(): void
    {
        $note = Note::factory()->create();
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'add' => [
                    'photo' => ['https://example.org/photo.jpg'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertSuccessful();
        $this->assertDatabaseHas('media_endpoint', [
            'path' => 'https://example.org/photo.jpg',
        ]);
    }

    #[Test]
    public function micropub_client_api_request_returns_error_trying_to_update_non_note_model(): void
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url').'/blog/A',
                'add' => [
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['error' => 'invalid'])
            ->assertStatus(500);
    }

    #[Test]
    public function micropub_client_api_request_returns_error_trying_to_update_non_existing_note(): void
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url').'/notes/ZZZZ',
                'add' => [
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['error' => 'invalid_request'])
            ->assertStatus(404);
    }

    #[Test]
    public function micropub_client_api_request_returns_error_when_trying_to_update_unsupported_property(): void
    {
        $note = Note::factory()->create();
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'morph' => [ // or any other unsupported update type
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['error' => 'unsupported_operation'])
            ->assertStatus(500);
    }

    #[Test]
    public function micropub_client_api_request_with_token_with_insufficient_scope_returns_error(): void
    {
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url').'/notes/B',
                'add' => [
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getTokenWithIncorrectScope()]
        );
        $response
            ->assertStatus(401)
            ->assertJson(['error' => 'insufficient_scope']);
    }

    #[Test]
    public function micropub_client_api_request_can_replace_note_syndication_targets(): void
    {
        $note = Note::factory()->create();
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'replace' => [
                    'syndication' => [
                        'https://www.swarmapp.com/checkin/the-id',
                        'https://www.facebook.com/post/the-id',
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertSuccessful();
        $this->assertDatabaseHas('notes', [
            'swarm_url' => 'https://www.swarmapp.com/checkin/the-id',
            'facebook_url' => 'https://www.facebook.com/post/the-id',
        ]);
    }

    #[Test]
    public function micropub_client_web_request_can_encode_token_within_the_form(): void
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

    #[Test]
    public function micropub_client_api_request_can_combine_replace_and_add_in_single_update(): void
    {
        $note = Note::factory()->create();
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'replace' => [
                    'content' => ['replaced content'],
                ],
                'add' => [
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertSuccessful();

        $note->refresh();
        $this->assertSame('replaced content', $note->content);
        $this->assertDatabaseHas('notes', [
            'swarm_url' => 'https://www.swarmapp.com/checkin/123',
        ]);
    }

    #[Test]
    public function micropub_client_api_request_can_delete_entire_property(): void
    {
        $note = Note::factory()->create([
            'swarm_url' => 'https://www.swarmapp.com/checkin/123',
            'facebook_url' => 'https://www.facebook.com/post/123',
        ]);
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'delete' => ['syndication'],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertSuccessful();

        $note->refresh();
        $this->assertNull($note->swarm_url);
        $this->assertNull($note->facebook_url);
        $this->assertNull($note->tweet_id);
    }

    #[Test]
    public function micropub_client_api_request_can_delete_specific_syndication_value(): void
    {
        $note = Note::factory()->create([
            'swarm_url' => 'https://www.swarmapp.com/checkin/123',
            'facebook_url' => 'https://www.facebook.com/post/123',
        ]);
        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'delete' => [
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertSuccessful();

        $note->refresh();
        $this->assertNull($note->swarm_url);
        $this->assertSame('https://www.facebook.com/post/123', $note->facebook_url);
    }

    #[Test]
    public function micropub_client_api_request_can_delete_photo(): void
    {
        $note = Note::factory()->create();
        $media = new Media;
        $media->path = 'https://example.org/photo.jpg';
        $media->type = 'image';
        $media->save();
        $note->media()->save($media);

        $response = $this->postJson(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'delete' => ['photo'],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertSuccessful();

        $this->assertDatabaseMissing('media_endpoint', [
            'path' => 'https://example.org/photo.jpg',
        ]);
    }

    #[Test]
    public function micropub_client_api_request_creates_articles_when_it_includes_the_name_property(): void
    {
        $faker = Factory::create();
        $name = $faker->text(50);
        $content = $faker->paragraphs(5, true);

        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'name' => [$name],
                    'content' => [$content],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response
            ->assertJson(['response' => 'created'])
            ->assertStatus(201);
        $this->assertDatabaseHas('articles', [
            'title' => $name,
            'main' => $content,
            'published' => true,
        ]);
        $response->assertHeader('Location');
        $this->assertStringStartsWith(config('app.url').'/blog/', $response->headers->get('Location'));
    }

    #[Test]
    public function micropub_client_api_request_creates_an_unpublished_article_when_post_status_is_draft(): void
    {
        $faker = Factory::create();
        $name = $faker->text(50);
        $content = $faker->paragraphs(5, true);

        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'name' => [$name],
                    'content' => [$content],
                    'post-status' => ['draft'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response
            ->assertJson(['response' => 'created'])
            ->assertStatus(201);
        $this->assertDatabaseHas('articles', [
            'title' => $name,
            'main' => $content,
            'published' => false,
        ]);
    }
}
