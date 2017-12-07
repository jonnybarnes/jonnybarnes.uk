<?php

namespace Tests\Unit;

use App\Tag;
use App\Note;
use Tests\TestCase;
use Thujohn\Twitter\Facades\Twitter;

class NotesTest extends TestCase
{
    /**
     * Test the getNoteAttribute method. This will then also call the
     * relevant sub-methods.
     *
     * @return void
     */
    public function test_get_note_attribute_method()
    {
        $expected = '<p>Having a <a rel="tag" class="p-category" href="/notes/tagged/beer">#beer</a> at the local. <span role="img" aria-label="beer mug">🍺</span></p>' . PHP_EOL;
        $note = Note::find(12);
        $this->assertEquals($expected, $note->note);
    }

    /**
     * Look for a default image in the contact’s h-card for the makeHCards method.
     *
     * @return void
     */
    public function test_default_image_used_in_makehcards_method()
    {
        $expected = '<p>Hi <span class="u-category h-card mini-h-card"><a class="u-url p-name" href="http://tantek.com">Tantek Çelik</a><span class="hovercard"> <a class="u-url" href="https://twitter.com/t"><img class="social-icon" src="/assets/img/social-icons/twitter.svg"> t</a><img class="u-photo" alt="" src="/assets/profile-images/default-image"></span></span></p>' . PHP_EOL;
        $note = Note::find(13);
        $this->assertEquals($expected, $note->note);
    }

    /**
     * Look for a specific profile image in the contact’s h-card.
     *
     * @return void
     */
    public function test_specific_profile_image_used_in_makehcards_method()
    {
        $expected = '<p>Hi <span class="u-category h-card mini-h-card"><a class="u-url p-name" href="https://aaronparecki.com">Aaron Parecki</a><span class="hovercard"><a class="u-url" href="https://www.facebook.com/123456"><img class="social-icon" src="/assets/img/social-icons/facebook.svg"> Facebook</a> <img class="u-photo" alt="" src="/assets/profile-images/aaronparecki.com/image"></span></span></p>' . PHP_EOL;
        $note = Note::find(14);
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
        $note = Note::find(15);
        $this->assertEquals($expected, $note->note);
    }

    public function test_shorturl_method()
    {
        $note = Note::find(14);
        $this->assertEquals(config('app.shorturl') . '/notes/E', $note->shorturl);
    }

    public function test_latlng_of_associated_place()
    {
        $note = Note::find(12); // should be having beer at bridgewater note
        $this->assertEquals('53.4983', $note->latitude);
        $this->assertEquals('-2.3805', $note->longitude);
    }

    public function test_latlng_returns_null_otherwise()
    {
        $note = Note::find(5);
        $this->assertNull($note->latitude);
        $this->assertNull($note->longitude);
    }

    public function test_address_attribute_for_places()
    {
        $note = Note::find(12);
        $this->assertEquals('The Bridgewater Pub', $note->address);
    }

    public function test_deleting_event_observer()
    {
        // first we’ll create a temporary note to delete
        $note = Note::create(['note' => 'temporary #temp']);
        $this->assertDatabaseHas('tags', [
            'tag' => 'temp',
        ]);
        $tag = Tag::where('tag', 'temp')->first();
        $note->forceDelete();
        $this->assertDatabaseMissing('note_tag', [
            'tag_id' => $tag->id,
        ]);
    }
}
