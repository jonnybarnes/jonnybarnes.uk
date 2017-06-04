<?php

namespace Tests\Unit;

use App\Place;
use Tests\TestCase;
use Phaza\LaravelPostgis\Geometries\Point;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PlacesTest extends TestCase
{
    /**
     * Test the near method returns a collection.
     *
     * @return void
     */
    public function test_near_method()
    {
        $nearby = Place::near(new Point(53.5, -2.38), 1000)->get();
        $this->assertEquals('the-bridgewater-pub', $nearby[0]->slug);
    }
}
