<?php

namespace Tests\Feature;

use Tests\TestCase;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OwnYourGramTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ownyourgram_post()
    {
        $response = $this->json(
            'POST',
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => ['How beautiful are the plates and chopsticks'],
                    'published' => [\Carbon\Carbon::now()->toIso8601String()],
                    'location' => ['geo:53.802419075834,-1.5431942917637'],
                    'syndication' => ['https://www.instagram.com/p/BVC_nVTBFfi/'],
                    'photo' => ['https://scontent-sjc2-1.cdninstagram.com/t51.2885-15/e35/18888604_425332491185600_326487281944756224_n.jpg'],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );

        $response->assertStatus(201)->assertJson([
            'response' => 'created'
        ]);
        $this->assertDatabaseHas('media_endpoint', [
            'path' => 'https://scontent-sjc2-1.cdninstagram.com/t51.2885-15/e35/18888604_425332491185600_326487281944756224_n.jpg'
        ]);
        $this->assertDatabaseHas('notes', [
            'note' => 'How beautiful are the plates and chopsticks',
            'instagram_url' => 'https://www.instagram.com/p/BVC_nVTBFfi/'
        ]);
    }

    private function getToken()
    {
        $signer = new Sha256();
        $token = (new Builder())
            ->set('client_id', 'https://ownyourgram.com')
            ->set('me', config('app.url'))
            ->set('scope', 'create')
            ->set('issued_at', time())
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }
}
