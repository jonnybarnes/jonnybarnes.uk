<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Check the `/contacts` page gives a good response.
     */
    #[Test]
    public function contacts_page_loads_without_error(): void
    {
        $response = $this->get('/contacts');
        $response->assertStatus(200);
    }

    /**
     * Test an individual contact page with default profile image.
     */
    #[Test]
    public function contact_page_should_fallback_to_default_profile_pic(): void
    {
        Contact::factory()->create([
            'nick' => 'tantek',
        ]);
        $response = $this->get('/contacts/tantek');
        $response->assertViewHas('image', '/assets/profile-images/default-image');
    }

    /**
     * Test an individual contact page with a specific profile image.
     */
    #[Test]
    public function contact_page_should_use_specific_profile_pic_if_present(): void
    {
        Contact::factory()->create([
            'nick' => 'aaron',
            'homepage' => 'https://aaronparecki.com',
        ]);
        $response = $this->get('/contacts/aaron');
        $response->assertViewHas('image', '/assets/profile-images/aaronparecki.com/image');
    }

    #[Test]
    public function unknown_contact_returns_not_found_response(): void
    {
        $response = $this->get('/contacts/unknown');
        $response->assertNotFound();
    }
}
