<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Note;
use Tests\TestCase;

class BridgyPosseTest extends TestCase
{
    /** @test */
    public function notesWeWantCopiedToTwitterShouldHaveNecessaryMarkup(): void
    {
        Contact::factory()->create([
            'nick' => 'joe',
            'twitter' => 'joe__',
        ]);
        $note = Note::factory()->create(['note' => 'Hi @joe']);

        $response = $this->get($note->longurl);

        $html = $response->content();
        $this->assertStringContainsString('p-bridgy-twitter-content', $html);
    }
}
