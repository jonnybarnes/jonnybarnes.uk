<?php

namespace App\Tests;

use BrowserKitTest;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MicropubTest extends BrowserKitTest
{
    use DatabaseTransactions;

    protected $appurl;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
    }

    public function testMicropubRequestWithoutToken()
    {
        $this->call('GET', $this->appurl . '/api/post');
        $this->assertResponseStatus(400);
        $this->seeJson(['error_description' => 'No token provided with request']);
    }

    public function testMicropubRequestWithoutValidToken()
    {
        $this->call('GET', $this->appurl . '/api/post', [], [], [], ['HTTP_Authorization' => 'Bearer abc123']);
        $this->assertResponseStatus(400);
        $this->seeJson(['error_description' => 'The provided token did not pass validation']);
    }

    public function testMicropubRequestWithValidToken()
    {
        $this->call('GET', $this->appurl . '/api/post', [], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $this->seeJson(['response' => 'token']);
    }

    public function testMicropubRequestForSyndication()
    {
        $this->call('GET', $this->appurl . '/api/post', ['q' => 'syndicate-to'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $this->seeJson(['uid' => 'https://twitter.com/jonnybarnes']);
    }

    public function testMicropubRequestForNearbyPlacesThatExist()
    {
        $this->call('GET', $this->appurl . '/api/post', ['q' => 'geo:53.5,-2.38'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $this->see('the-bridgewater-pub');
    }

    public function testMicropubRequestForNearbyPlacesThatExistWithUncertaintyParameter()
    {
        $this->call('GET', $this->appurl . '/api/post', ['q' => 'geo:53.5,-2.38;u=35'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $this->see('the-bridgewater-pub');
    }

    public function testMicropubRequestForNearbyPlacesThatDoNotExist()
    {
        $this->call('GET', $this->appurl . '/api/post', ['q' => 'geo:1.23,4.56'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $this->see('[]');
    }

    public function testMicropubRequestForConfig()
    {
        $this->call('GET', $this->appurl . '/api/post', ['q' => 'config'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $this->seeJson(['uid' => 'https://twitter.com/jonnybarnes']);
    }

    public function testMicropubRequestCreateNewNote()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $this->call(
            'POST',
            $this->appurl . '/api/post',
            [
                'h' => 'entry',
                'content' => $note
            ],
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $this->seeInDatabase('notes', ['note' => $note]);
    }

    public function testMicropubRequestCreateNewPlace()
    {
        $this->call(
            'POST',
            $this->appurl . '/api/post',
            [
                'h' => 'card',
                'name' => 'The Barton Arms',
                'geo' => 'geo:53.4974,-2.3768'
            ],
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );
        $this->seeInDatabase('places', ['slug' => 'the-barton-arms']);
    }

    public function testMicropubJSONRequestCreateNewNote()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $this->json(
            'POST',
            $this->appurl . '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        )->seeJson([
            'response' => 'created'
        ])->assertResponseStatus(201);
    }

    public function testMicropubJSONRequestCreateNewNoteWithoutToken()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $this->json(
            'POST',
            $this->appurl . '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                ],
            ]
        )->seeJson([
            'response' => 'error',
            'error' => 'no_token'
        ])->assertResponseStatus(400);
    }

    public function testMicropubJSONRequestCreateNewNoteWithInvalidToken()
    {
        $faker = \Faker\Factory::create();
        $note = $faker->text;
        $this->json(
            'POST',
            $this->appurl . '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => [$note],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getInvalidToken()]
        )->seeJson([
            'response' => 'error',
            'error' => 'invalid_token'
        ]);
    }

    public function testMicropubJSONRequestCreateNewPlace()
    {
        $faker = \Faker\Factory::create();
        $this->json(
            'POST',
            $this->appurl . '/api/post',
            [
                'type' => ['h-card'],
                'properties' => [
                    'name' => $faker->name,
                    'geo' => 'geo:' . $faker->latitude . ',' . $faker->longitude
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        )->seeJson([
            'response' => 'created'
        ])->assertResponseStatus(201);
    }

    public function testMicropubJSONRequestCreateNewPlaceWithoutToken()
    {
        $faker = \Faker\Factory::create();
        $this->json(
            'POST',
            $this->appurl . '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'name' => $faker->name,
                    'geo' => 'geo:' . $faker->latitude . ',' . $faker->longitude
                ],
            ]
        )->seeJson([
            'response' => 'error',
            'error' => 'no_token'
        ])->assertResponseStatus(400);
    }

    public function testMicropubJSONRequestCreateNewPlaceWithInvalidToken()
    {
        $faker = \Faker\Factory::create();
        $this->json(
            'POST',
            $this->appurl . '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'name' => $faker->name,
                    'geo' => 'geo:' . $faker->latitude . ',' . $faker->longitude
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getInvalidToken()]
        )->seeJson([
            'response' => 'error',
            'error' => 'invalid_token'
        ]);
    }

    public function testMicropubJSONRequestCreateNewPlaceWithUncertaintyParam()
    {
        $faker = \Faker\Factory::create();
        $this->json(
            'POST',
            $this->appurl . '/api/post',
            [
                'type' => ['h-card'],
                'properties' => [
                    'name' => $faker->name,
                    'geo' => 'geo:' . $faker->latitude . ',' . $faker->longitude . ';u=35'
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        )->seeJson([
            'response' => 'created'
        ])->assertResponseStatus(201);
    }

    private function getToken()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('client_id', 'https://quill.p3k.io')
            ->set('me', 'https://jonnybarnes.localhost')
            ->set('scope', 'post')
            ->set('issued_at', time())
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }

    private function getInvalidToken()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('client_id', 'https://quill.p3k.io')
            ->set('me', 'https://jonnybarnes.localhost')
            ->set('scope', 'view')
            ->set('issued_at', time())
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }
}
