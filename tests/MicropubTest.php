<?php

namespace App\Tests;

use TestCase;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MicropubTest extends TestCase
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
        $this->see('No OAuth token sent with request.');
    }

    public function testMicropubRequestWithoutValidToken()
    {
        $this->call('GET', $this->appurl . '/api/post', [], [], [], ['HTTP_Authorization' => 'Bearer abc123']);
        $this->assertResponseStatus(400);
        $this->see('Invalid token');
    }

    public function testMicropubRequestWithValidToken()
    {
        $this->call('GET', $this->appurl . '/api/post', [], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $this->see('me=https%3A%2F%2Fjbl5.dev');
    }

    public function testMicropubRequestForSyndication()
    {
        $this->call('GET', $this->appurl . '/api/post', ['q' => 'syndicate-to'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $this->see('twitter.com%2Fjonnybarnes');
    }

    public function testMicropubRequestForNearbyPlacesThatExist()
    {
        $this->call('GET', $this->appurl . '/api/post', ['q' => 'geo:53.5,-2.38'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $this->see('the-bridgewater-pub');
    }

    public function testMicropubRequestForNearbyPlacesThatDoNotExist()
    {
        $this->call('GET', $this->appurl . '/api/post', ['q' => 'geo:1.23,4.56'], [], [], ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]);
        $this->see('[]');
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
        $faker = \Faker\Factory::create();
        $note = $faker->text;
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

    private function getToken()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('client_id', 'https://quill.p3k.io')
            ->set('me', 'https://jbl5.dev')
            ->set('scope', 'post')
            ->set('issued_at', time())
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }
}
