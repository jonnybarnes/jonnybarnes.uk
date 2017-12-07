<?php

namespace Tests\Unit;

use App\Article;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArticlesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sluggable_method()
    {
        $article = new Article();
        $article->title = 'My Title';
        $article->main = 'Content';
        $article->save();

        $this->assertEquals('my-title', $article->titleurl);
    }

    public function test_markdown_conversion()
    {
        $article = new Article();
        $article->main = 'Some *markdown*';

        $this->assertEquals('<p>Some <em>markdown</em></p>'.PHP_EOL, $article->html);
    }

    public function test_time_attributes()
    {
        $article = Article::create([
            'title' => 'Test',
            'main' => 'test',
        ]);

        $this->assertEquals($article->w3c_time, $article->updated_at->toW3CString());
        $this->assertEquals($article->tooltip_time, $article->updated_at->toRFC850String());
        $this->assertEquals($article->human_time, $article->updated_at->diffForHumans());
        $this->assertEquals($article->pubdate, $article->updated_at->toRSSString());
    }

    public function test_link_accessor()
    {
        $article = Article::create([
            'title' => 'Test',
            'main' => 'Test',
        ]);
        $article->title = 'Test Title';

        $this->assertEquals(
            '/blog/' . date('Y') . '/' . date('m') . '/test',
            $article->link
        );
    }

    public function test_date_scope()
    {
        $yearAndMonth = Article::date(date('Y'), date('m'))->get();
        $this->assertTrue(count($yearAndMonth) === 1);

        $monthDecember = Article::date(date('Y') - 1, 12)->get();
        $this->assertTrue(count($monthDecember) === 0);

        $monthNotDecember = Article::date(date('Y') - 1, 1)->get();
        $this->assertTrue(count($monthNotDecember) === 0);

        $emptyScope = Article::date()->get();
        $this->assertTrue(count($emptyScope) === 1);
    }
}
