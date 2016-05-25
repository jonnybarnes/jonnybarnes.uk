<?php

namespace App\Tests;

use TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotesTest extends TestCase
{
    protected $appurl;
    protected $notesController;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
        $this->notesController = new \App\Http\Controllers\NotesController();
    }

    /**
     * Test the `/notes` page returns 200, this should
     * mean the database is being hit.
     *
     * @return void
     */
    public function testNotesPage()
    {
        $this->visit($this->appurl . '/notes')
             ->assertResponseOk();
    }

    /**
     * Test a specific note so that `singleNote()` get called.
     *
     * @return void
     */
    public function testSpecificNote()
    {
        $this->visit($this->appurl . '/notes/B')
             ->see('#beer');
    }

    /**
     * Test that `/note/{decID}` redirects to `/notes/{nb60id}`.
     *
     * @return void
     */
    public function testDecIDRedirect()
    {
        $this->get($this->appurl . '/note/11')
             ->assertRedirectedTo(config('app.url') . '/notes/B');
    }

    /**
     * Visit the tagged page and see text from the note.
     *
     * @return void
     */
    public function testTaggedNotesPage()
    {
        $this->visit($this->appurl . '/notes/tagged/beer')
             ->see('at the local.');
    }

    /**
     * Look for a default image in the contact’s h-card.
     *
     * @return void
     */
    public function testDefaultImageUsed()
    {
        $this->visit($this->appurl . '/notes/C')
             ->see('<img class="u-photo photo logo" alt="" src="/assets/profile-images/default-image">');
    }

    /**
     * Look for a specific profile image in the contact’s h-card.
     *
     * @return void
     */
    public function testProfileImageUsed()
    {
        $this->visit($this->appurl . '/notes/D')
             ->see('<img class="u-photo photo logo" alt="" src="/assets/profile-images/aaronparecki.com/image">');
    }

    /**
     * Look for twitter URL when there’s no associated contact.
     *
     * @return void
     */
    public function testTwitterLinkCreatedWhenNoContactFound()
    {
        $this->visit($this->appurl . '/notes/E')
             ->see('<a href="https://twitter.com/bob">@bob</a>');
    }

    /**
     * Test hashtag linking.
     *
     * @return void
     */
    public function testHashtags()
    {
        $this->visit($this->appurl . '/notes/B')
             ->see('<a rel="tag" class="p-category" href="/notes/tagged/beer">#beer</a>');
    }

    /**
     * Look for the client name after the note.
     *
     * @return void
     */
    public function testClientNameDisplayed()
    {
        $this->visit($this->appurl . '/notes/D')
             ->see('JBL5');
    }

    /**
     * Look for the client URL after the note.
     *
     * @return void
     */
    public function testClientURLDisplayed()
    {
        $this->visit($this->appurl . '/notes/E')
             ->see('quill.p3k.io');
    }

    /**
     * Test the bridgy url shim method.
     *
     * @return void
     */
    public function testBridgy()
    {
        $url = 'https://brid-gy.appspot.com/comment/twitter/jonnybarnes/497778866816299008/497781260937203712';
        $expected = 'https://twitter.com/_/status/497781260937203712';
        $this->assertEquals($expected, $this->notesController->bridgyReply($url));
    }

    /**
     * Test a correct profile link is formed from a generic URL.
     *
     * @return void
     */
    public function testCreatePhotoLinkWithGenericURL()
    {
        $homepage = 'https://example.org';
        $expected = '/assets/profile-images/example.org/image';
        $this->assertEquals($expected, $this->notesController->createPhotoLink($homepage));
    }

    /**
     * Test a correct profile link is formed from a twitter URL.
     *
     * @return void
     */
    public function testCreatePhotoLinkWithTwitterProfileImageURL()
    {
        $twitterProfileImage = 'http://pbs.twimg.com/1234';
        $expected = 'https://pbs.twimg.com/1234';
        $this->assertEquals($expected, $this->notesController->createPhotoLink($twitterProfileImage));
    }

    /**
     * Test `null` is returned for a twitter profile.
     *
     * @return void
     */
    public function testCreatePhotoLinkWithTwitterURL()
    {
        $twitterURL = 'https://twitter.com/example';
        $this->assertNull($this->notesController->createPhotoLink($twitterURL));
    }
}
