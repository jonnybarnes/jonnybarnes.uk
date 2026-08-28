<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\About;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function about_page_shows_content(): void
    {
        About::factory()->create([
            'content' => 'This is the about page content.',
        ]);

        $this->get('/about')
            ->assertSee('This is the about page content.');
    }
}
