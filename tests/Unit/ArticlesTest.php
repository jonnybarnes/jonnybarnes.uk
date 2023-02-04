<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ArticlesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function titleSlugIsGeneratedAutomatically(): void
    {
        $article = new Article();
        $article->title = 'My Title';
        $article->main = 'Content';
        $article->save();

        $this->assertEquals('my-title', $article->titleurl);
    }

    /** @test */
    public function markdownContentIsConverted(): void
    {
        $article = new Article();
        $article->main = 'Some *markdown*';

        $this->assertEquals('<p>Some <em>markdown</em></p>' . PHP_EOL, $article->html);
    }

    /** @test */
    public function weGenerateTheDifferentTimeAttributes(): void
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

    /** @test */
    public function weGenerateTheArticleLinkFromTheSlug(): void
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

    /** @test */
    public function dateScopeReturnsExpectedArticles(): void
    {
        Article::factory()->create([
            'created_at' => Carbon::now()->subYear()->toDateTimeString(),
            'updated_at' => Carbon::now()->subYear()->toDateTimeString(),
        ]);
        Article::factory()->create();

        $yearAndMonth = Article::date(date('Y'), date('m'))->get();
        $this->assertCount(1, $yearAndMonth);

        $priorYear = Article::date(date('Y') - 2, 1)->get();
        $this->assertCount(0, $priorYear);

        $emptyScope = Article::date()->get();
        $this->assertCount(2, $emptyScope);
    }

    /** @test */
    public function dateScopeReturnsExpectedArticlesForDecember(): void
    {
        Article::factory()->create([
            'created_at' => Carbon::now()->setDay(11)->setMonth(11)->toDateTimeString(),
            'updated_at' => Carbon::now()->setDay(11)->setMonth(11)->toDateTimeString(),
        ]);

        Article::factory()->create([
            'created_at' => Carbon::now()->setMonth(12)->setDay(12)->toDateTimeString(),
            'updated_at' => Carbon::now()->setMonth(12)->setDay(12)->toDateTimeString(),
        ]);

        $this->assertCount(1, Article::date(date('Y'), 12)->get());
    }
}
