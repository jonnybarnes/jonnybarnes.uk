<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Check the `/contacts` page gives a good response.
     *
     * @test
     */
    public function contactsPageLoadsWithoutError(): void
    {
        $response = $this->get('/contacts');
        $response->assertStatus(200);
    }

    /**
     * Test an individual contact page with default profile image.
     *
     * @test
     */
    public function contactPageShouldFallbackToDefaultProfilePic(): void
    {
        Contact::factory()->create([
            'nick' => 'tantek',
        ]);
        $response = $this->get('/contacts/tantek');
        $response->assertViewHas('image', '/assets/profile-images/default-image');
    }

    /**
     * Test an individual contact page with a specific profile image.
     *
     * @test
     */
    public function contactPageShouldUseSpecificProfilePicIfPresent(): void
    {
        Contact::factory()->create([
            'nick' => 'aaron',
            'homepage' => 'https://aaronparecki.com',
        ]);
        $response = $this->get('/contacts/aaron');
        $response->assertViewHas('image', '/assets/profile-images/aaronparecki.com/image');
    }

    /** @test */
    public function unknownContactReturnsNotFoundResponse(): void
    {
        $response = $this->get('/contacts/unknown');
        $response->assertNotFound();
    }
}
