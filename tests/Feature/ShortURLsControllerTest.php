<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ShortURLsControllerTest extends TestCase
{
    /** @test */
    public function shortDomainRedirectsToLongDomain(): void
    {
        $response = $this->get('https://' . config('app.shorturl'));
        $response->assertRedirect(config('app.url'));
    }

    /** @test */
    public function shortDomainSlashAtRedirectsToTwitter(): void
    {
        $response = $this->get('https://' . config('app.shorturl') . '/@');
        $response->assertRedirect('https://twitter.com/jonnybarnes');
    }

    /** @test */
    public function shortDomainSlashTRedirectsToLongDomainSlashNotes(): void
    {
        $response = $this->get('https://' . config('app.shorturl') . '/t/E');
        $response->assertRedirect(config('app.url') . '/notes/E');
    }

    /** @test */
    public function shortDomainSlashBRedirectsToLongDomainSlashBlog(): void
    {
        $response = $this->get('https://' . config('app.shorturl') . '/b/1');
        $response->assertRedirect(config('app.url') . '/blog/s/1');
    }
}
