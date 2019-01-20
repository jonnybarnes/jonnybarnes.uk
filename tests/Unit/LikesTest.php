<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Like;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LikesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_setting_author_url()
    {
        $like = new Like();
        $like->author_url = 'https://joe.bloggs/';
        $this->assertEquals('https://joe.bloggs', $like->author_url);
    }

    public function test_plaintext_like_content()
    {
        $like = new Like();
        $like->url = 'https://example.org/post/123';
        $like->content = 'some plaintext content';
        $like->save();

        $this->assertEquals('some plaintext content', $like->content);
    }

    public function test_html_like_content_is_filtered()
    {
        $htmlEvil = <<<HTML
<div class="h-entry">
    <div class="e-content">
        <p>Hello</p>
        <img src="javascript:evil();" onload="evil();" />
    </div>
</div>
HTML;
        $htmlFiltered = <<<HTML
<p>Hello</p>
HTML;
        $like = new Like();
        $like->url = 'https://example.org/post/123';
        $like->content = $htmlEvil;
        $like->save();

        // HTMLPurifer will leave the whitespace before the <img> tag
        // trim it, saving whitespace in $htmlFilteres can get removed by text editors
        $this->assertEquals($htmlFiltered, trim($like->content));
    }
}
