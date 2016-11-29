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
        $syndication = [
            [
                'target' => 'https://twitter.com/jonnybarnes',
                'name' => 'jonnybarnes on Twitter',
            ]
        ];
        $this->withSession([
            'me' => $this->appurl,
            'syndication' => $syndication,
        ])->visit($this->appurl . '/notes/new')
          ->see($this->appurl)
          ->see('https://twitter.com/jonnybarnes');
    }

    public function testClientCreatesNewNoteWithTag()
    {
        //in this test, the syndication targets are blank
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
        //my client has made a request to my endpoint, which then adds
        //to the db, so database transaction don’t work
        //so lets manually delete the new entry
        //first, if we are using algolia, we need to delete it
        if (env('SCOUT_DRIVER') == 'algolia') {
            //we need to allow the index to update in order to query it
            sleep(2);
            $client = new \AlgoliaSearch\Client(env('ALGOLIA_APP_ID'), env('ALGOLIA_SECRET'));
            $index = $client->initIndex('notes');
            //here we query for the new note and tell algolia too delete it
            $res = $index->deleteByQuery('Fake note from');
            if ($res == 0) {
                //somehow the new not didn’t get deleted
                $this->fail('Didn’t delete the note from the index');
            }
        }
        $newNote = \App\Note::where('note', $note)->first();
        $newNote->forceDelete();

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
