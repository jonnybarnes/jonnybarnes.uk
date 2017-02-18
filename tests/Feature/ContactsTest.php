<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactsTest extends TestCase
{
    /**
     * Check the `/contatcs` page gives a good response.
     *
     * @return void
     */
    public function test_contacts_page()
    {
        $response = $this->get('/contacts');
        $response->assertStatus(200);
    }

    /**
     * Test an individual contact page with default profile image.
     *
     * @return void
     */
    public function test_contact_page_with_default_pic()
    {
        $response = $this->get('/contacts/tantek');
        $response->assertViewHas('image', '/assets/profile-images/default-image');
    }

    /**
     * Test an individual contact page with a specific profile image.
     *
     * @return void
     */
    public function test_contact_page_with_specific_pic()
    {
        $response = $this->get('/contacts/tantek');
        $response->assertViewHas('image', '/assets/profile-images/aaronparecki.com/image');
    }
}
