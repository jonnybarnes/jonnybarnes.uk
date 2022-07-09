<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tests\TestToken;

class OwnYourGramTest extends TestCase
{
    use RefreshDatabase;
    use TestToken;

    /** @test */
    public function postingInstagramUrlSavesMediaPath(): void
    {
        $response = $this->json(
            'POST',
            '/api/post',
            [
                'type' => ['h-entry'],
                'properties' => [
                    'content' => ['How beautiful are the plates and chopsticks'],
                    'published' => [Carbon::now()->toIso8601String()],
                    'location' => ['geo:53.802419075834,-1.5431942917637'],
                    'syndication' => ['https://www.instagram.com/p/BVC_nVTBFfi/'],
                    'photo' => [
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        'https://scontent-sjc2-1.cdninstagram.com/t51.2885-15/e35/18888604_425332491185600_326487281944756224_n.jpg',
                    ],
                ],
            ],
            ['HTTP_Authorization' => 'Bearer ' . $this->getToken()]
        );

        $response->assertStatus(201)->assertJson([
            'response' => 'created',
        ]);
        $this->assertDatabaseHas('media_endpoint', [
            // phpcs:ignore Generic.Files.LineLength.TooLong
            'path' => 'https://scontent-sjc2-1.cdninstagram.com/t51.2885-15/e35/18888604_425332491185600_326487281944756224_n.jpg',
        ]);
        $this->assertDatabaseHas('notes', [
            'note' => 'How beautiful are the plates and chopsticks',
            'instagram_url' => 'https://www.instagram.com/p/BVC_nVTBFfi/',
        ]);
    }
}
