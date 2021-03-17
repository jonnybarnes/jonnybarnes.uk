<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class BridgyPosseTest extends TestCase
{
    /** @test */
    public function notesWeWantCopiedToTwitterShouldHaveNecessaryMarkup(): void
    {
        $response = $this->get('/notes/4');

        $html = $response->content();
        $this->assertTrue(is_string(mb_stristr($html, 'p-bridgy-twitter-content')));
    }
}
