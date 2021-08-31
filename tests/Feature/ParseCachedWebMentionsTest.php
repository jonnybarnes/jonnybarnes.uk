<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\WebMention;
use Illuminate\FileSystem\FileSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ParseCachedWebMentionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        mkdir(storage_path('HTML') . '/https/aaronpk.localhost/reply', 0777, true);
        mkdir(storage_path('HTML') . '/http/tantek.com', 0777, true);
        copy(__DIR__ . '/../aaron.html', storage_path('HTML') . '/https/aaronpk.localhost/reply/1');
        copy(__DIR__ . '/../tantek.html', storage_path('HTML') . '/http/tantek.com/index.html');
    }

    /** @test */
    public function parseWebmentionHtml(): void
    {
        $webmentionAaron = WebMention::factory()->create([
            'source' => 'https://aaronpk.localhost/reply/1',
            'created_at' => Carbon::now()->subDays(5),
            'updated_at' => Carbon::now()->subDays(5),
        ]);
        $webmentionTantek = WebMention::factory()->create([
            'source' => 'http://tantek.com/',
            'created_at' => Carbon::now()->subDays(5),
            'updated_at' => Carbon::now()->subDays(5),
        ]);
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

        $webmentionAaron = WebMention::find($webmentionAaron->id);
        $webmentionTantek = WebMention::find($webmentionTantek->id);

        $this->assertTrue($webmentionAaron->updated_at->gt($webmentionAaron->created_at));
        $this->assertTrue($webmentionTantek->updated_at->gt($webmentionTantek->created_at));
    }

    protected function tearDown(): void
    {
        $fs = new FileSystem();
        if ($fs->exists(storage_path() . '/HTML/https')) {
            $fs->deleteDirectory(storage_path() . '/HTML/https');
        }
        if ($fs->exists(storage_path() . '/HTML/http')) {
            $fs->deleteDirectory(storage_path() . '/HTML/http');
        }

        parent::tearDown();
    }
}
