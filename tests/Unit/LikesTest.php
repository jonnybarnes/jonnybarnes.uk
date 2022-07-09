<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function weCanSetTheAuthorUrl(): void
    {
        $like = new Like();
        $like->author_url = 'https://joe.bloggs/';
        $this->assertEquals('https://joe.bloggs', $like->author_url);
    }

    /** @test */
    public function weDoNotModifyPlainTextContent(): void
    {
        $like = new Like();
        $like->url = 'https://example.org/post/123';
        $like->content = 'some plaintext content';
        $like->save();

        $this->assertEquals('some plaintext content', $like->content);
    }

    /** @test */
    public function weCanHandleBlankContent(): void
    {
        $like = new Like();
        $like->url = 'https://example.org/post/123';
        $like->content = null;
        $like->save();

        $this->assertNull($like->content);
    }

    /** @test */
    public function htmlLikeContentIsFiltered(): void
    {
        $htmlEvil = <<<'HTML'
        <div class="h-entry">
            <div class="e-content">
                <p>Hello</p>
                <img src="javascript:evil();" onload="evil();" />
            </div>
        </div>
        HTML;
        $htmlFiltered = <<<'HTML'
        <p>Hello</p>
                <img />
        HTML;
        $like = new Like();
        $like->url = 'https://example.org/post/123';
        $like->content = $htmlEvil;
        $like->save();

        // HTMLPurifier will leave the whitespace before the <img> tag
        // trim it, saving whitespace in $htmlFiltered can get removed by text editors
        $this->assertEquals($htmlFiltered, trim($like->content));
    }
}
