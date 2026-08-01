<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\DownloadWebMention;
use Illuminate\FileSystem\FileSystem;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DownloadWebMentionJobTest extends TestCase
{
    protected function tearDown(): void
    {
        $fs = new FileSystem;
        if ($fs->exists(storage_path().'/HTML/https')) {
            $fs->deleteDirectory(storage_path().'/HTML/https');
        }
        parent::tearDown();
    }

    #[Test]
    public function html_is_saved_by_job(): void
    {
        $this->assertFileDoesNotExist(storage_path('HTML/https'));
        $source = 'https://example.org/reply/1';
        $html = <<<'HTML'
        <div class="h-entry">
            <a class="u-like-of" href=""></a>
        </div>
        HTML;
        $html = str_replace('href=""', 'href="'.config('app.url').'/notes/A"', $html);
        Http::fake([
            'example.org/*' => Http::response($html, 200, ['X-Foo' => 'Bar']),
        ]);

        $job = new DownloadWebMention($source);
        $job->handle();

        $this->assertFileExists(storage_path('HTML/https'));

        $job->handle();

        $this->assertFileDoesNotExist(storage_path('HTML/https/example.org/reply').'/1.'.date('Y-m-d').'.backup');
    }

    #[Test]
    public function html_and_backup_saved_by_job(): void
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
        $html = str_replace('href=""', 'href="'.config('app.url').'/notes/A"', $html);
        $html2 = str_replace('href=""', 'href="'.config('app.url').'/notes/A"', $html2);
        Http::fake([
            'example.org/*' => Http::sequence()
                ->push($html, 200, ['X-Foo' => 'Bar'])
                ->push($html2, 200, ['X-Foo' => 'Bar']),
        ]);

        $job = new DownloadWebMention($source);
        $job->handle();

        $this->assertFileExists(storage_path('HTML/https'));

        $job->handle();

        $this->assertFileExists(storage_path('HTML/https/example.org/reply').'/1.'.date('Y-m-d').'.backup');
    }

    #[Test]
    public function index_html_file_is_saved_by_job_for_urls_ending_with_slash(): void
    {
        $this->assertFileDoesNotExist(storage_path('HTML/https'));
        $source = 'https://example.org/reply-one/';
        $html = <<<'HTML'
        <div class="h-entry">
            <a class="u-like-of" href=""></a>
        </div>
        HTML;
        $html = str_replace('href=""', 'href="'.config('app.url').'/notes/A"', $html);
        Http::fake([
            'example.org/*' => Http::response($html, 200, ['X-Foo' => 'Bar']),
        ]);

        $job = new DownloadWebMention($source);
        $job->handle();

        $this->assertFileExists(storage_path('HTML/https/example.org/reply-one/index.html'));
    }
}
