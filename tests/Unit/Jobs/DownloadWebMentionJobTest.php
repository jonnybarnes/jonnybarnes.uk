<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\DownloadWebMention;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\FileSystem\FileSystem;
use Tests\TestCase;

class DownloadWebMentionJobTest extends TestCase
{
    protected function tearDown(): void
    {
        $fs = new FileSystem();
        if ($fs->exists(storage_path() . '/HTML/https')) {
            $fs->deleteDirectory(storage_path() . '/HTML/https');
        }
        parent::tearDown();
    }

    /** @test */
    public function htmlIsSavedByJob(): void
    {
        $this->assertFileDoesNotExist(storage_path('HTML/https'));
        $source = 'https://example.org/reply/1';
        $html = <<<'HTML'
        <div class="h-entry">
            <a class="u-like-of" href=""></a>
        </div>
        HTML;
        $html = str_replace('href=""', 'href="' . config('app.url') . '/notes/A"', $html);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $html),
            new Response(200, ['X-Foo' => 'Bar'], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $job = new DownloadWebMention($source);
        $job->handle($client);

        $this->assertFileExists(storage_path('HTML/https'));

        $job->handle($client);

        $this->assertFileDoesNotExist(storage_path('HTML/https/example.org/reply') . '/1.' . date('Y-m-d') . '.backup');
    }

    /** @test */
    public function htmlAndBackupSavedByJob(): void
    {
        $this->assertFileDoesNotExist(storage_path('HTML/https'));
        $source = 'https://example.org/reply/1';
        $html = <<<'HTML'
        <div class="h-entry">
            <a class="u-like-of" href=""></a>
        </div>
        HTML;
        $html2 = <<<'HTML'
        <div class="h-entry">
            <a class="u-like-of" href=""></a>
            <a class="u-repost-of" href=""></a>
        </div>
        HTML;
        $html = str_replace('href=""', 'href="' . config('app.url') . '/notes/A"', $html);
        $html2 = str_replace('href=""', 'href="' . config('app.url') . '/notes/A"', $html2);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $html),
            new Response(200, ['X-Foo' => 'Bar'], $html2),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $job = new DownloadWebMention($source);
        $job->handle($client);

        $this->assertFileExists(storage_path('HTML/https'));

        $job->handle($client);

        $this->assertFileExists(storage_path('HTML/https/example.org/reply') . '/1.' . date('Y-m-d') . '.backup');
    }

    /** @test */
    public function indexHtmlFileIsSavedByJobForUrlsEndingWithSlash(): void
    {
        $this->assertFileDoesNotExist(storage_path('HTML/https'));
        $source = 'https://example.org/reply-one/';
        $html = <<<'HTML'
        <div class="h-entry">
            <a class="u-like-of" href=""></a>
        </div>
        HTML;
        $html = str_replace('href=""', 'href="' . config('app.url') . '/notes/A"', $html);
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $html),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $job = new DownloadWebMention($source);
        $job->handle($client);

        $this->assertFileExists(storage_path('HTML/https/example.org/reply-one/index.html'));
    }
}
