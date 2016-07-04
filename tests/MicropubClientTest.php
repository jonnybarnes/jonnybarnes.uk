<?php

namespace App\Tests;

use TestCase;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MicropubClientTest extends TestCase
{
    protected $appurl;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
    }

    /**
     * Test the client gets shown for an unauthorised request.
     *
     * @return void
     */
    public function testClientPageUnauthorised()
    {
        $this->visit($this->appurl . '/notes/new')
             ->see('IndieAuth');
    }

    public function testClientPageRecentAuth()
    {
        $syndication = ['https://twitter.com/jonnybarnes'];
        $this->withSession([
            'me' => $this->appurl,
            'syndication' => $syndication,
        ])->visit($this->appurl . '/notes/new')
          ->see($this->appurl)
          ->see('https://twitter.com/jonnybarnes');
    }

    /**
     * This currently creates a new note that stays in the database.
     */
    public function testClientCreatesNewNoteWithTag()
    {
        $faker = \Faker\Factory::create();
        $note = 'Fake note from #PHPUnit: ' . $faker->text;
        $this->withSession([
            'me' => $this->appurl,
            'token' => $this->getToken()
        ])->visit($this->appurl . '/notes/new')
          ->type($note, 'content')
          ->press('Submit');
        $this->seeInDatabase('notes', ['note' => $note]);
        $this->visit($this->appurl . '/notes/tagged/PHPUnit')
             ->see('PHPUnit');

    }

    private function getToken()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('client_id', 'https://quill.p3k.io')
            ->set('me', $this->appurl)
            ->set('scope', 'post')
            ->set('issued_at', time())
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }
}
