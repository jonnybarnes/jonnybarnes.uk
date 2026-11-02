<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendWebMentions;
use App\Jobs\SyndicateNoteToBluesky;
use App\Jobs\SyndicateNoteToMastodon;
use App\Models\Article;
use App\Models\Bookmark;
use App\Models\Like;
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
        $response->assertStatus(401);
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
        $response->assertStatus(401);
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
        $response->assertStatus(403);
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
            ->assertStatus(403);
    }

    #[Test]
    public function micropub_client_api_request_for_unsupported_post_type_returns_error(): void
    {
        $this->mock(ExceptionHandler::class)->shouldNotReceive('report');

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
                'error' => 'invalid_request',
                'error_description' => "Unsupported Micropub request type 'unsupported'",
            ])
            ->assertStatus(400);
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
            ->assertJson(['error' => 'invalid_request'])
            ->assertStatus(400);
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
            ->assertJson(['error' => 'invalid_request'])
            ->assertStatus(400);
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
            ->assertStatus(403)
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
    public function micropub_client_api_request_for_a_like_returns_our_permalink_in_the_location_header(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'like-of' => ['https://example.org/liked-post'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $like = Like::where('url', 'https://example.org/liked-post')->sole();
        $response
            ->assertStatus(201)
            ->assertHeader('Location', config('app.url').'/likes/'.$like->id);
    }

    #[Test]
    public function micropub_client_api_request_for_a_bookmark_returns_our_permalink_in_the_location_header(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'bookmark-of' => ['https://example.org/bookmarked-page'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $bookmark = Bookmark::where('url', 'https://example.org/bookmarked-page')->sole();
        $response
            ->assertStatus(201)
            ->assertHeader('Location', config('app.url').'/bookmarks/'.$bookmark->id);
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

    #[Test]
    public function micropub_client_api_request_updates_an_existing_draft_article_with_the_same_name(): void
    {
        $draft = Article::create([
            'title' => 'WireGuard',
            'main' => 'Early draft content',
            'published' => false,
        ]);

        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'name' => ['WireGuard'],
                    'content' => ['Finished content'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response
            ->assertJson(['response' => 'created'])
            ->assertStatus(201);
        $this->assertSame(1, Article::where('title', 'WireGuard')->count());
        $this->assertDatabaseHas('articles', [
            'id' => $draft->id,
            'title' => 'WireGuard',
            'main' => 'Finished content',
            'published' => true,
        ]);
    }

    #[Test]
    public function micropub_client_api_request_errors_when_an_article_with_the_same_name_is_already_published(): void
    {
        Article::create([
            'title' => 'WireGuard',
            'main' => 'Published content',
            'published' => true,
        ]);

        $response = $this->postJson(
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'name' => ['WireGuard'],
                    'content' => ['Some other content'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response
            ->assertJson(['error' => 'invalid_request'])
            ->assertStatus(400);
        $this->assertSame(1, Article::where('title', 'WireGuard')->count());
    }

    #[Test]
    public function micropub_client_web_request_without_h_property_creates_note(): void
    {
        $response = $this->post(
            '/api/post',
            ['content' => 'A note without an explicit type'],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response->assertStatus(201)->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('notes', ['note' => 'A note without an explicit type']);
    }

    #[Test]
    public function micropub_client_api_request_without_type_creates_note(): void
    {
        $response = $this->postJson(
            '/api/post',
            ['properties' => ['content' => ['A JSON note without an explicit type']]],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response->assertStatus(201)->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('notes', ['note' => 'A JSON note without an explicit type']);
    }

    #[Test]
    public function micropub_client_web_request_can_update_note(): void
    {
        $note = Note::factory()->create();

        $response = $this->post(
            '/api/post',
            [
                'action' => 'update',
                'url' => $note->uri,
                'replace' => ['content' => ['replaced via form']],
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response->assertStatus(200)->assertJson(['response' => 'updated']);
        $this->assertSame('replaced via form', $note->refresh()->content);
    }

    #[Test]
    public function micropub_client_web_request_with_unsupported_action_is_not_treated_as_a_new_note(): void
    {
        $note = Note::factory()->create();
        $noteCount = Note::count();

        $response = $this->post(
            '/api/post',
            ['action' => 'delete', 'url' => $note->uri],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response->assertStatus(400)->assertJson(['error' => 'invalid_request']);
        $this->assertSame($noteCount, Note::count());
    }

    #[Test]
    public function micropub_client_web_request_creates_note_with_photo(): void
    {
        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => 'A note with a photo',
                'photo' => 'https://example.org/photo.jpg',
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response->assertStatus(201);
        $note = Note::where('note', 'A note with a photo')->firstOrFail();
        $this->assertSame(['https://example.org/photo.jpg'], $note->media->pluck('path')->all());
    }

    #[Test]
    public function micropub_client_web_request_accepts_single_syndication_target_without_brackets(): void
    {
        Queue::fake();
        SyndicationTarget::factory()->create([
            'uid' => 'https://mastodon.social/@jonnybarnes',
            'service_name' => 'Mastodon',
        ]);

        $response = $this->post(
            '/api/post',
            [
                'h' => 'entry',
                'content' => 'Syndicate me',
                'mp-syndicate-to' => 'https://mastodon.social/@jonnybarnes',
            ],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response->assertStatus(201);
        Queue::assertPushed(SyndicateNoteToMastodon::class);
    }

    #[Test]
    public function source_query_returns_the_raw_note_as_microformats2(): void
    {
        $note = Note::factory()->create([
            'note' => 'Some *markdown* #indieweb',
            'in_reply_to' => 'https://example.org/post',
            'mastodon_url' => 'https://mastodon.example/@me/1',
        ]);
        $media = Media::factory()->create(['path' => 'media/alt.jpg', 'type' => 'image']);
        $note->media()->attach($media->id, ['alt_text' => 'A description', 'order' => 0]);

        $response = $this->getJson(
            '/api/post?q=source&url='.urlencode($note->uri),
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response->assertOk()->assertExactJson([
            'type' => ['h-entry'],
            'properties' => [
                'content' => ['Some *markdown* #indieweb'],
                'published' => [$note->created_at->toIso8601String()],
                'in-reply-to' => ['https://example.org/post'],
                'category' => ['indieweb'],
                'photo' => [['value' => $media->url, 'alt' => 'A description']],
                'syndication' => ['https://mastodon.example/@me/1'],
            ],
        ]);
    }

    #[Test]
    public function source_query_returns_the_location_of_a_note(): void
    {
        $headers = ['HTTP_Authorization' => 'Bearer '.$this->getToken()];
        $geoNote = Note::factory()->create(['location' => '1.23, 4.56']);
        $place = Place::factory()->create();
        $placeNote = Note::factory()->create();
        $placeNote->place()->associate($place)->save();

        $this->getJson('/api/post?q=source&properties[]=location&url='.urlencode($geoNote->uri), $headers)
            ->assertExactJson(['properties' => ['location' => ['geo:1.23,4.56']]]);
        $this->getJson('/api/post?q=source&properties[]=location&url='.urlencode($placeNote->uri), $headers)
            ->assertExactJson(['properties' => ['location' => [$place->uri]]]);
    }

    #[Test]
    public function source_query_only_returns_the_requested_properties(): void
    {
        $note = Note::factory()->create(['note' => 'Hello']);

        $response = $this->getJson(
            '/api/post?q=source&properties[]=content&url='.urlencode($note->uri),
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $response->assertOk()->assertExactJson(['properties' => ['content' => ['Hello']]]);
    }

    #[Test]
    public function source_query_with_no_matching_properties_returns_an_empty_object(): void
    {
        $note = Note::factory()->create();

        $response = $this->getJson(
            '/api/post?q=source&properties[]=nonsense&url='.urlencode($note->uri),
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        );

        $this->assertSame('{"properties":{}}', $response->assertOk()->getContent());
    }

    #[Test]
    public function source_query_reports_invalid_urls(): void
    {
        $headers = ['HTTP_Authorization' => 'Bearer '.$this->getToken()];
        $note = Note::factory()->create();

        $this->getJson('/api/post?q=source', $headers)
            ->assertStatus(400)->assertJson(['error' => 'invalid_request']);
        $this->getJson('/api/post?q=source&url='.urlencode('https://other.example/notes/'.$note->nb60id), $headers)
            ->assertStatus(400)->assertJson(['error' => 'invalid_request']);
        $this->getJson('/api/post?q=source&url='.urlencode(config('app.url').'/blog/A'), $headers)
            ->assertStatus(400)->assertJson(['error' => 'invalid_request']);
        $this->getJson('/api/post?q=source&url='.urlencode(config('app.url').'/notes/ZZZZ'), $headers)
            ->assertStatus(404)->assertJson(['error' => 'invalid_request']);
        $this->getJson('/api/post?q=source&url='.urlencode(config('app.url').'/notes/zzzzzzzzzz'), $headers)
            ->assertStatus(404)->assertJson(['error' => 'invalid_request']);
    }

    #[Test]
    public function unknown_and_malformed_queries_return_an_error(): void
    {
        $headers = ['HTTP_Authorization' => 'Bearer '.$this->getToken()];

        foreach (['q=nonsense', 'q[]=config', 'q=geo:junk', 'q=geo:1.2.3,4'] as $query) {
            $this->getJson('/api/post?'.$query, $headers)
                ->assertStatus(400)->assertJson(['error' => 'invalid_request']);
        }
    }

    #[Test]
    public function update_with_a_malformed_value_is_rejected_without_changing_the_note(): void
    {
        $headers = ['HTTP_Authorization' => 'Bearer '.$this->getToken()];
        $note = Note::factory()->create(['note' => 'original']);

        $operations = [
            ['replace' => ['content' => 'hello']],
            ['replace' => ['content' => []]],
            ['replace' => ['content' => [123]]],
            ['replace' => ['content' => ['replaced'], 'syndication' => 'https://www.swarmapp.com/1']],
            ['replace' => ['content']],
            ['add' => ['syndication' => [['nested']]]],
            ['delete' => [['content']]],
            ['delete' => ['syndication' => 'https://www.swarmapp.com/1']],
        ];

        foreach ($operations as $operation) {
            $this->postJson('/api/post', ['action' => 'update', 'url' => $note->uri, ...$operation], $headers)
                ->assertStatus(400)->assertJson(['error' => 'invalid_request']);
        }

        $this->assertSame('original', $note->refresh()->getRawOriginal('note'));
    }

    #[Test]
    public function update_that_would_do_nothing_is_rejected_without_changing_the_note(): void
    {
        $headers = ['HTTP_Authorization' => 'Bearer '.$this->getToken()];
        $note = Note::factory()->create(['note' => 'original']);
        $mediaCount = Media::count();

        $operations = [
            ['replace' => []],
            ['replace' => ['category' => ['indieweb']]],
            ['replace' => ['content' => ['changed'], 'category' => ['indieweb']]],
            ['replace' => ['photo' => ['https://example.org/photo.jpg']]],
            ['replace' => ['syndication' => ['https://unknown.example/1']]],
            ['add' => ['category' => ['indieweb']]],
            ['add' => ['content' => ['more']]],
            ['add' => ['syndication' => ['https://unknown.example/1']]],
            ['add' => ['photo' => ['http://example.org/photo.jpg']]],
            ['delete' => ['category']],
            ['delete' => ['category' => ['indieweb']]],
            ['delete' => ['syndication' => ['https://unknown.example/1']]],
        ];

        foreach ($operations as $operation) {
            $this->postJson('/api/post', ['action' => 'update', 'url' => $note->uri, ...$operation], $headers)
                ->assertStatus(400)->assertJson(['error' => 'invalid_request']);
        }

        $this->assertSame('original', $note->refresh()->getRawOriginal('note'));
        $this->assertSame($mediaCount, Media::count());
    }

    #[Test]
    public function update_can_add_and_delete_mastodon_and_bluesky_syndication_urls(): void
    {
        $headers = ['HTTP_Authorization' => 'Bearer '.$this->getToken()];
        SyndicationTarget::factory()->create(['uid' => 'https://mastodon.example/@me', 'service_name' => 'Mastodon']);
        $note = Note::factory()->create();
        $mastodon = 'https://mastodon.example/@me/1';
        $bluesky = 'https://bsky.app/profile/jonnybarnes.uk/post/1';

        $this->postJson('/api/post', ['action' => 'update', 'url' => $note->uri, 'add' => ['syndication' => [$mastodon, $bluesky]]], $headers)
            ->assertOk();
        $note->refresh();
        $this->assertSame($mastodon, $note->mastodon_url);
        $this->assertSame($bluesky, $note->bluesky_url);

        $this->postJson('/api/post', ['action' => 'update', 'url' => $note->uri, 'delete' => ['syndication' => [$mastodon]]], $headers)
            ->assertOk();
        $note->refresh();
        $this->assertNull($note->mastodon_url);
        $this->assertSame($bluesky, $note->bluesky_url);

        $this->postJson('/api/post', ['action' => 'update', 'url' => $note->uri, 'delete' => ['syndication']], $headers)
            ->assertOk();
        $this->assertNull($note->refresh()->bluesky_url);
    }

    #[Test]
    public function update_rejects_mastodon_urls_from_instances_we_do_not_syndicate_to(): void
    {
        SyndicationTarget::factory()->create(['uid' => 'https://mastodon.example/@me', 'service_name' => 'Mastodon']);
        SyndicationTarget::factory()->create(['uid' => 'https://other.example/@me', 'service_name' => 'Pixelfed']);
        $note = Note::factory()->create(['mastodon_url' => 'https://mastodon.example/@me/1']);

        foreach (['https://mastodon.social/@me/2', 'https://mastodon.example.evil/@me/2', 'https://other.example/@me/2'] as $url) {
            $this->postJson(
                '/api/post',
                ['action' => 'update', 'url' => $note->uri, 'add' => ['syndication' => [$url]]],
                ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
            )->assertStatus(400)->assertJson(['error' => 'invalid_request']);
        }

        $this->assertSame('https://mastodon.example/@me/1', $note->refresh()->mastodon_url);
    }

    #[Test]
    public function update_with_an_unusable_url_is_rejected(): void
    {
        $headers = ['HTTP_Authorization' => 'Bearer '.$this->getToken()];
        $note = Note::factory()->create(['note' => 'original']);
        $replace = ['replace' => ['content' => ['changed']]];

        $this->postJson('/api/post', ['action' => 'update', ...$replace], $headers)->assertStatus(400);
        $this->postJson('/api/post', ['action' => 'update', 'url' => ['a'], ...$replace], $headers)->assertStatus(400);
        $this->postJson('/api/post', ['action' => 'update', 'url' => 'https://other.example/notes/'.$note->nb60id, ...$replace], $headers)->assertStatus(400);
        $this->postJson('/api/post', ['action' => 'update', 'url' => config('app.url').'/notes/zzzzzzzzzz', ...$replace], $headers)->assertStatus(404);

        $this->assertSame('original', $note->refresh()->getRawOriginal('note'));
    }

    #[Test]
    public function creating_with_a_wrongly_shaped_property_returns_an_error(): void
    {
        $headers = ['HTTP_Authorization' => 'Bearer '.$this->getToken()];
        $noteCount = Note::count();

        $this->postJson('/api/post', ['type' => ['h-entry'], 'properties' => ['content' => ['x'], 'mp-syndicate-to' => 'https://a.example']], $headers)
            ->assertStatus(400)->assertJson(['error' => 'invalid_request']);
        $this->postJson('/api/post', ['type' => ['h-entry'], 'properties' => ['content' => [123]]], $headers)
            ->assertStatus(400)->assertJson(['error' => 'invalid_request']);
        $this->post('/api/post', ['h' => 'entry', 'content' => ['a', 'b']], $headers)
            ->assertStatus(400)->assertJson(['error' => 'invalid_request']);

        $this->assertSame($noteCount, Note::count());
    }

    #[Test]
    public function creating_with_an_invalid_published_date_does_not_leave_a_note_behind(): void
    {
        Queue::fake();
        $noteCount = Note::count();

        $this->postJson(
            '/api/post',
            ['type' => ['h-entry'], 'properties' => ['content' => ['x'], 'published' => ['not a date']]],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        )->assertStatus(400)->assertJson(['error' => 'invalid_request']);

        $this->assertSame($noteCount, Note::count());
    }

    #[Test]
    public function creating_with_a_photo_object_without_alt_text_succeeds(): void
    {
        Queue::fake();

        $this->postJson(
            '/api/post',
            ['type' => ['h-entry'], 'properties' => ['content' => ['x'], 'photo' => [['value' => 'https://example.org/no-alt.jpg']]]],
            ['HTTP_Authorization' => 'Bearer '.$this->getToken()]
        )->assertStatus(201);

        $this->assertDatabaseHas('media_endpoint', ['path' => 'https://example.org/no-alt.jpg']);
    }
}
