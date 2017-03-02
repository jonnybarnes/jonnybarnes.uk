<?php

namespace Tests\Unit;

use App\Note;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotesTest extends TestCase
{
    /**
     * Test the getNoteAttribute method. This note will check the markdown,
     * emoji-a11y, and hashtag sub-methods.
     *
     * @return void
     */
    public function test_get_note_attribute_method()
    {
        $expected = '<p>Having a <a rel="tag" class="p-category" href="/notes/tagged/beer">#beer</a> at the local. <span role="img" aria-label="beer mug">🍺</span></p>' . PHP_EOL;
        $note = Note::find(11);
        $this->assertEquals($expected, $note->note);
    }

    /**
     * Look for a default image in the contact’s h-card for the makeHCards method.
     *
     * @return void
     */
    public function test_default_image_used_in_makehcards_method()
    {
        $expected = '<p>Hi <span class="u-category h-card">
    <a class="mini-h-card u-url p-name" href="http://tantek.com">
        <img class="u-photo" alt="" src="/assets/profile-images/default-image">
        Tantek Çelik
    </a>
        <a class="u-url" href="https://twitter.com/t"></a></span></p>' . PHP_EOL;
        $note = Note::find(12);
        $this->assertEquals($expected, $note->note);
    }

    /**
     * Look for a specific profile image in the contact’s h-card.
     *
     * @return void
     */
    public function test_specific_profile_image_used_in_makehcards_method()
    {
        $expected = '<p>Hi <span class="u-category h-card">
    <a class="mini-h-card u-url p-name" href="https://aaronparecki.com">
        <img class="u-photo" alt="" src="/assets/profile-images/aaronparecki.com/image">
        Aaron Parecki
    </a>
        <a class="u-url" href="https://twitter.com/aaronpk"></a></span></p>' . PHP_EOL;
        $note = Note::find(13);
        $this->assertEquals($expected, $note->note);
    }

    /**
     * Look for twitter URL when there’s no associated contact.
     *
     * @return void
     */
    public function test_twitter_link_created_when_no_contact_found()
    {
        $expected = '<p>Hi <a href="https://twitter.com/bob">@bob</a></p>' . PHP_EOL;
        $note = Note::find(14);
        $this->assertEquals($expected, $note->note);
    }
}
