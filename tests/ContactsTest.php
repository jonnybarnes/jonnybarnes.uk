<?php

namespace App\Tests;

use TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactsTest extends TestCase
{
    protected $appurl;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
    }

    /**
     * Test the `/contacts` page and see if response is OK.
     *
     * @return void
     */
    public function testContactsPage()
    {
        $this->visit($this->appurl . '/contacts')
             ->assertResponseOK();
    }

    /**
     * Test an individual contact page with default profile image.
     *
     * @return void
     */
    public function testContactPageWithDefaultPic()
    {
        $this->visit($this->appurl . '/contacts/tantek')
             ->see('<img src="/assets/profile-images/default-image" alt="" class="u-photo">');
    }

    /**
     * Test an individual contact page with a specific profile image.
     *
     * @return void
     */
    public function testContactPageWithSpecificPic()
    {
        $this->visit($this->appurl . '/contacts/aaron')
             ->see('<img src="/assets/profile-images/aaronparecki.com/image" alt="" class="u-photo">');
    }
}
