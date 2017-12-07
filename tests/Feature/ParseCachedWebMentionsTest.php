<?php

namespace Tests\Feature;

use App\WebMention;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ParseCachedWebMentionsTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();

        mkdir(storage_path('HTML') . '/https/aaronpk.localhost/reply', 0777, true);
        mkdir(storage_path('HTML') . '/http/tantek.com', 0777, true);
        copy(__DIR__.'/../aaron.html', storage_path('HTML') . '/https/aaronpk.localhost/reply/1');
        copy(__DIR__.'/../tantek.html', storage_path('HTML') . '/http/tantek.com/index.html');
    }

    public function test_parsing_html()
    {
        $this->assertFileExists(storage_path('HTML') . '/https/aaronpk.localhost/reply/1');
        $this->assertFileExists(storage_path('HTML') . '/http/tantek.com/index.html');
        $htmlAaron = file_get_contents(storage_path('HTML') . '/https/aaronpk.localhost/reply/1');
        $htmlAaron = str_replace('href="/notes', 'href="' . config('app.url') . '/notes', $htmlAaron);
        $htmlAaron = str_replace('datetime=""', 'dateime="' . carbon()->now()->toIso8601String() . '"', $htmlAaron);
        file_put_contents(storage_path('HTML') . '/https/aaronpk.localhost/reply/1', $htmlAaron);
        $htmlTantek = file_get_contents(storage_path('HTML') . '/http/tantek.com/index.html');
        $htmlTantek = str_replace('href="/notes', 'href="' . config('app.url') . '/notes', $htmlTantek);
        $htmlTantek = str_replace('datetime=""', 'dateime="' . carbon()->now()->toIso8601String() . '"', $htmlTantek);
        file_put_contents(storage_path('HTML') . '/http/tantek.com/index.html', $htmlTantek);

        Artisan::call('webmentions:parsecached');

        $webmentionAaron = WebMention::find(1);
        $webmentionTantek = WebMention::find(2);
        $this->assertTrue($webmentionAaron->updated_at->gt($webmentionAaron->created_at));
        $this->assertTrue($webmentionTantek->updated_at->gt($webmentionTantek->created_at));
    }

    public function tearDown()
    {
        unlink(storage_path('HTML') . '/https/aaronpk.localhost/reply/1');
        rmdir(storage_path('HTML') . '/https/aaronpk.localhost/reply');
        rmdir(storage_path('HTML') . '/https/aaronpk.localhost');
        rmdir(storage_path('HTML') . '/https');
        unlink(storage_path('HTML') . '/http/tantek.com/index.html');
        rmdir(storage_path('HTML') . '/http/tantek.com');
        rmdir(storage_path('HTML') . '/http');

        parent::tearDown();
    }
}
