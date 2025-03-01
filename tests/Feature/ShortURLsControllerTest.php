<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShortURLsControllerTest extends TestCase
{
    #[Test]
    public function shortDomainRedirectsToLongDomain(): void
    {
        $response = $this->get('https://' . config('url.shorturl'));
        $response->assertRedirect(config('app.url'));
    }

    #[Test]
    public function shortDomainSlashAtRedirectsToTwitter(): void
    {
        $response = $this->get('https://' . config('url.shorturl') . '/@');
        $response->assertRedirect('https://twitter.com/jonnybarnes');
    }

    #[Test]
    public function shortDomainSlashTRedirectsToLongDomainSlashNotes(): void
    {
        $response = $this->get('https://' . config('url.shorturl') . '/t/E');
        $response->assertRedirect(config('app.url') . '/notes/E');
    }

    #[Test]
    public function shortDomainSlashBRedirectsToLongDomainSlashBlog(): void
    {
        $response = $this->get('https://' . config('url.shorturl') . '/b/1');
        $response->assertRedirect(config('app.url') . '/blog/s/1');
    }
}
