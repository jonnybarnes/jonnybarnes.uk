<?php

namespace Tests\Feature;

use Tests\TestCase;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MicropubControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test a GET request for the micropub endpoint without a token gives a
     * 400 response. Also check the error message.
     *
     * @return void
     */
    public function test_micropub_request_without_token_returns_401_response()
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
    public function test_micropub_request_without_valid_token_returns_400_response()
    {
        $response = $this->call('GET', '/api/post', [], [], [], ['HTTP_Authorization' => 'Bearer abc123']);
        $response->assertStatus(400);
        $response->assertJsonFragment(['error_description' => 'The provided token did not pass validation']);
    }

    /**
     * Test a GET request for the micropub endpoint with a valid token gives a
     * 200 response. Check token information is returned in the response.
     *
     * @return void
     */
    public function test_micropub_request_with_valid_token_returns_200_response()
    {
        $response = $this->call('GET', '/api/post', [], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['response' => 'token']);
    }

    /**
     * Test a GET request for syndication targets.
     *
     * @return void
     */
    public function test_micropub_request_for_syndication_targets()
    {
        $response = $this->call('GET', '/api/post', ['q' => 'syndicate-to'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJsonFragment(['uid' => 'https://twitter.com/jonnybarnes']);
    }

    /**
     * Test a request for places.
     *
     * @return void
     */
    public function test_micropub_request_for_nearby_places()
    {
        $response = $this->call('GET', '/api/post', ['q' => 'geo:53.5,-2.38'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJson(['places' => [['slug' =>'the-bridgewater-pub']]]);
    }

    /**
     * Test a request for places, this time with an uncertainty parameter.
     *
     * @return void
     */
    public function test_micropub_request_for_nearby_places_with_uncertainty_parameter()
    {
        $response = $this->call('GET', '/api/post', ['q' => 'geo:53.5,-2.38;u=35'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJson(['places' => [['slug' => 'the-bridgewater-pub']]]);
    }

    /**
     * Test a request for places, where there will be an “empty” response.
     *
     * @return void
     */
    public function test_micropub_request_for_nearby_places_where_non_exist()
    {
        $response = $this->call('GET', '/api/post', ['q' => 'geo:1.23,4.56'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJson(['places' => []]);
    }

    /**
     * Test a request for the micropub config.
     *
     * @return void
     */
    public function test_micropub_request_for_config()
    {
        $response = $this->call('GET', '/api/post', ['q' => 'config'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $response->assertJsonFragment(['uid' => 'https://twitter.com/jonnybarnes']);
    }

    /**
     * Test a valid micropub requests creates a new note.
     *
     * @return void
     */
    public function test_micropub_request_creates_new_note()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->call(
            'POST',
            '/api/post',
            [
                'h' => 'entry',
                'content' => $note
            ],
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('notes', ['note' => $note]);
    }

    /**
     * Test a valid micropub requests creates a new place.
     *
     * @return void
     */
    public function test_micropub_request_creates_new_place()
    {
        $response = $this->call(
            'POST',
            '/api/post',
            [
                'h' => 'card',
                'name' => 'The Barton Arms',
                'geo' => 'geo:53.4974,-2.3768'
            ],
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response->assertJson(['response' => 'created']);
        $this->assertDatabaseHas('places', ['slug' => 'the-barton-arms']);
    }

    /**
     * Test a valid micropub requests using JSON syntax creates a new note.
     *
     * @return void
     */
    public function test_micropub_request_with_json_syntax_creates_new_note()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->json(
            'POST',
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertStatus(201)
            ->assertJson(['response' => 'created']);
    }

    /**
     * Test a micropub requests using JSON syntax without a token returns an
     * error. Also check the message.
     *
     * @return void
     */
    public function test_micropub_request_with_json_syntax_without_token_returns_error()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->json(
            'POST',
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
     * Test a micropub requests using JSON syntax without a valis token returns
     * an error. Also check the message.
     *
     * @return void
     */
    public function test_micropub_request_with_json_syntax_with_invalid_token_returns_error()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $response = $this->json(
            'POST',
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getInvalidToken()]
        );
        $response
            ->assertJson([
                'response' => 'error',
                'error' => 'insufficient_scope'
            ])
            ->assertStatus(401);
    }

    public function test_micropub_request_with_json_syntax_creates_new_place()
    {
        $faker = \Faker\Factory::create();
        $response = $this->json(
            'POST',
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

    public function test_micropub_request_with_json_syntax_and_uncertainty_parameter_creates_new_place()
    {
        $faker = \Faker\Factory::create();
        $response = $this->json(
            'POST',
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

    public function test_micropub_request_with_json_syntax_update_replace_post()
    {
        $response = $this->json(
            'POST',
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

    public function test_micropub_request_with_json_syntax_update_add_post()
    {
        $response = $this->json(
            'POST',
            '/api/post',
            [
                'action' => 'update',
                'url' => config('app.url') . '/notes/A',
                'add' => [
                    'syndication' => ['https://www.swarmapp.com/checkin/123'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $response
            ->assertJson(['response' => 'updated'])
            ->assertStatus(200);
        $this->assertDatabaseHas('notes', [
            'swarm_url' => 'https://www.swarmapp.com/checkin/123'
        ]);
    }

    /**
     * Generate a valid token to be used in the tests.
     *
     * @return Lcobucci\JWT\Token\Plain $token
     */
    private function getToken()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('client_id', 'https://quill.p3k.io')
            ->set('me', 'https://jonnybarnes.localhost')
            ->set('scope', 'create update')
            ->set('issued_at', time())
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }

    /**
     * Generate an invalid token to be used in the tests.
     *
     * @return Lcobucci\JWT\Token\Plain $token
     */
    private function getInvalidToken()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('client_id', 'https://quill.p3k.io')
            ->set('me', 'https://jonnybarnes.localhost')
            ->set('scope', 'view') //error here
            ->set('issued_at', time())
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }
}
