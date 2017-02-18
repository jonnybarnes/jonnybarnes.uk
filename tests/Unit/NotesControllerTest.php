<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\NotesController;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotesControllerTest extends TestCase
{
    protected $notesController;

    public function __construct()
    {
        $this->notesController = new NotesController();
    }

    /**
     * Test a correct profile link is formed from a generic URL.
     *
     * @return void
     */
    public function test_create_photo_link_with_non_cached_image()
    {
        $notesController = new \App\Http\Controllers\NotesController();
        $homepage = 'https://example.org/profile.png';
        $expected = 'https://example.org/profile.png';
        $this->assertEquals($expected, $this->notesController->createPhotoLink($homepage));
    }

    /**
     * Test a correct profile link is formed from a generic URL (cached).
     *
     * @return void
     */
    public function test_create_photo_link_with_cached_image()
    {
        $notesController = new \App\Http\Controllers\NotesController();
        $homepage = 'https://aaronparecki.com/profile.png';
        $expected = '/assets/profile-images/aaronparecki.com/image';
        $this->assertEquals($expected, $this->notesController->createPhotoLink($homepage));
    }

    /**
     * Test a correct profile link is formed from a twitter URL.
     *
     * @return void
     */
    public function test_create_photo_link_with_twimg_profile_image_url()
    {
        $notesController = new \App\Http\Controllers\NotesController();
        $twitterProfileImage = 'http://pbs.twimg.com/1234';
        $expected = 'https://pbs.twimg.com/1234';
        $this->assertEquals($expected, $this->notesController->createPhotoLink($twitterProfileImage));
    }

    /**
     * Test `null` is returned for a twitter profile.
     *
     * @return void
     */
    public function test_create_photo_link_with_cached_twitter_url()
    {
        $twitterURL = 'https://twitter.com/example';
        $expected = 'https://pbs.twimg.com/static_profile_link.jpg';
        Cache::put($twitterURL, $expected, 1);
        $this->assertEquals($expected, $this->notesController->createPhotoLink($twitterURL));
    }
}
