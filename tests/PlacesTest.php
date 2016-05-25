<?php

namespace App\Tests;

use TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PlacesTest extends TestCase
{
    protected $appurl;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
    }

    /**
     * Test the `/places` page for OK response.
     *
     * @return void
     */
    public function testPlacesPage()
    {
        $this->visit($this->appurl . '/places')
             ->assertResponseOK();
    }

    /**
     * Test a specific place.
     *
     * @return void
     */
    public function testSinglePlace()
    {
        $this->visit($this->appurl . '/places/the-bridgewater-pub')
             ->see('The Bridgewater Pub');
    }

    /**
     * Test the nearby method returns a collection.
     *
     * @return void
     */
    public function testNearbyMethod()
    {
        $nearby = \App\Place::near(53.5, -2.38, 1000);
        $this->assertEquals('the-bridgewater-pub', $nearby[0]->slug);
    }
}
