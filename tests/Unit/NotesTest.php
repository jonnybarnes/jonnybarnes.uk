<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use App\Models\{Media, Note, Tag};
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test the getNoteAttribute method. This will then also call the
     * relevant sub-methods.
     *
     * @test
     * @return void
     */
    public function getNoteAttributeMethodCallsSubMethods(): void
    {
        // phpcs:ignore
        $expected = '<p>Having a <a rel="tag" class="p-category" href="/notes/tagged/beer">#beer</a> at the local. 🍺</p>' . PHP_EOL;
        $note = Note::find(2);
        $this->assertEquals($expected, $note->note);
    }

    /**
     * Look for a default image in the contact’s h-card for the makeHCards method.
     *
     * @test
     * @return void
     */
    public function defaultImageUsedAsFallbackInMakehcardsMethod(): void
    {
        // phpcs:ignore
        $expected = '<p>Hi <span class="u-category h-card mini-h-card"><a class="u-url p-name" href="http://tantek.com">Tantek Çelik</a><span class="hovercard"> <a class="u-url" href="https://twitter.com/t"><img class="social-icon" src="/assets/img/social-icons/twitter.svg"> t</a><img class="u-photo" alt="" src="/assets/profile-images/default-image"></span></span></p>' . PHP_EOL;
        $note = Note::find(4);
        $this->assertEquals($expected, $note->note);
    }

    /**
     * Look for a specific profile image in the contact’s h-card.
     *
     * @test
     * @return void
     */
    public function specificProfileImageUsedInMakehcardsMethod(): void
    {
        // phpcs:ignore
        $expected = '<p>Hi <span class="u-category h-card mini-h-card"><a class="u-url p-name" href="https://aaronparecki.com">Aaron Parecki</a><span class="hovercard"><a class="u-url" href="https://www.facebook.com/123456"><img class="social-icon" src="/assets/img/social-icons/facebook.svg"> Facebook</a> <img class="u-photo" alt="" src="/assets/profile-images/aaronparecki.com/image"></span></span></p>' . PHP_EOL;
        $note = Note::find(5);
        $this->assertEquals($expected, $note->note);
    }

    /**
     * Look for twitter URL when there’s no associated contact.
     *
     * @test
     * @return void
     */
    public function twitterLinkIsCreatedWhenNoContactFound(): void
    {
        $expected = '<p>Hi <a href="https://twitter.com/bob">@bob</a></p>' . PHP_EOL;
        $note = Note::find(6);
        $this->assertEquals($expected, $note->note);
    }

    /** @test */
    public function shorturlMethodReturnsExpectedValue(): void
    {
        $note = Note::find(14);
        $this->assertEquals(config('app.shorturl') . '/notes/E', $note->shorturl);
    }

    /** @test */
    public function weGetLatitudeLongitudeValuesOfAssociatedPlaceOfNote(): void
    {
        $note = Note::find(2); // should be having beer at bridgewater note
        $this->assertEquals('53.4983', $note->latitude);
        $this->assertEquals('-2.3805', $note->longitude);
    }

    /** @test */
    public function whenNoAssociatedPlaceWeGetNullForLatitudeLongitudeValues(): void
    {
        $note = Note::find(5);
        $this->assertNull($note->latitude);
        $this->assertNull($note->longitude);
    }

    /** @test */
    public function weCanGetAddressAttributeForAssociatedPlace(): void
    {
        $note = Note::find(2);
        $this->assertEquals('The Bridgewater Pub', $note->address);
    }

    /** @test */
    public function deletingNotesAlsoDeletesTagsViaTheEventObserver(): void
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

    /** @test */
    public function saveBlankNotesAsNull(): void
    {
        $note = new Note(['note' => '']);

        $this->assertNull($note->note);
    }

    /** @test */
    public function reverseGeocodeAnAttraction(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $json = <<<JSON
        {"place_id":"198791063","licence":"Data © OpenStreetMap contributors, ODbL 1.0. https:\/\/osm.org\/copyright","osm_type":"relation","osm_id":"5208404","lat":"51.50084125","lon":"-0.142990166340849","display_name":"Buckingham Palace, Ambassador's Court, St. James's, Victoria, Westminster, London, Greater London, England, SW1E 6LA, United Kingdom","address":{"attraction":"Buckingham Palace","road":"Ambassador's Court","neighbourhood":"St. James's","suburb":"Victoria","city":"London","state_district":"Greater London","state":"England","postcode":"SW1E 6LA","country":"UK","country_code":"gb"},"boundingbox":["51.4997342","51.5019473","-0.143984","-0.1413002"]}
        JSON;
        // phpcs:enable Generic.Files.LineLength.TooLong
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $json),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $this->app->instance(Client::class, $client);

        $note = new Note();
        $address = $note->reverseGeoCode(51.50084, -0.14264);

        $this->assertEquals(
            '<span class="p-locality">Victoria, London</span>, <span class="p-country-name">UK</span>',
            $address
        );
    }

    /** @test */
    public function reverseGeocodeASuburb(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $json = <<<JSON
        {"place_id":"96518506","licence":"Data © OpenStreetMap contributors, ODbL 1.0. https:\/\/osm.org\/copyright","osm_type":"way","osm_id":"94107885","lat":"51.0225764535969","lon":"0.906664040464189","display_name":"Melon Lane, Newchurch, Shepway, Kent, South East, England, TN29 0AS, United Kingdom","address":{"road":"Melon Lane","suburb":"Newchurch","city":"Shepway","county":"Kent","state_district":"South East","state":"England","postcode":"TN29 0AS","country":"UK","country_code":"gb"},"boundingbox":["51.0140377","51.0371494","0.8873312","0.9109506"]}
        JSON;
        // phpcs:enable Generic.Files.LineLength.TooLong
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $json),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $this->app->instance(Client::class, $client);

        $note = new Note();
        $address = $note->reverseGeoCode(51.02, 0.91);

        $this->assertEquals(
            '<span class="p-locality">Newchurch, Shepway</span>, <span class="p-country-name">UK</span>',
            $address
        );
    }

    /** @test */
    public function reverseGeocodeACity(): void
    {
        // Note I’ve modified this JSON response so it only contains the
        // city the Uni is in
        // phpcs:disable Generic.Files.LineLength.TooLong
        $json = <<<JSON
        {"place_id":"198561071","licence":"Data © OpenStreetMap contributors, ODbL 1.0. https:\/\/osm.org\/copyright","osm_type":"relation","osm_id":"1839026","lat":"53.46600455","lon":"-2.23300880782987","display_name":"University of Manchester - Main Campus, Brunswick Street, Curry Mile, Ardwick, Manchester, Greater Manchester, North West England, England, M13 9NR, United Kingdom","address":{"university":"University of Manchester - Main Campus","city":"Manchester","county":"Greater Manchester","state_district":"North West England","state":"England","postcode":"M13 9NR","country":"UK","country_code":"gb"},"boundingbox":["53.4598667","53.4716848","-2.2390346","-2.2262754"]}
        JSON;
        // phpcs:enable Generic.Files.LineLength.TooLong
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $json),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $this->app->instance(Client::class, $client);

        $note = new Note();
        $address = $note->reverseGeoCode(53.466277988406, -2.2304474827445);

        $this->assertEquals(
            '<span class="p-locality">Manchester</span>, <span class="p-country-name">UK</span>',
            $address
        );
    }

    /** @test */
    public function reverseGeocodeACounty(): void
    {
        // Note I’ve removed everything below county to test for querires where
        // that’s all that is returned
        // phpcs:disable Generic.Files.LineLength.TooLong
        $json = <<<JSON
        {"place_id":"98085404","licence":"Data © OpenStreetMap contributors, ODbL 1.0. https:\/\/osm.org\/copyright","osm_type":"way","osm_id":"103703318","lat":"51.0997470194065","lon":"0.609897771085209","display_name":"Biddenden, Ashford, Kent, South East, England, TN27 8ET, United Kingdom","address":{"county":"Kent","state_district":"South East","state":"England","postcode":"TN27 8ET","country":"UK","country_code":"gb"},"boundingbox":["51.0986632","51.104459","0.5954434","0.6167775"]}
        JSON;
        // phpcs:enable Generic.Files.LineLength.TooLong
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $json),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $this->app->instance(Client::class, $client);

        $note = new Note();
        $address = $note->reverseGeoCode(51.1, 0.61);

        $this->assertEquals('<span class="p-region">Kent</span>, <span class="p-country-name">UK</span>', $address);
    }

    /** @test */
    public function reverseGeocodeACountry(): void
    {
        // Note I’ve removed everything below country to test for querires where
        // that’s all that is returned
        // phpcs:disable Generic.Files.LineLength.TooLong
        $json = <<<JSON
        {"place_id":"120553244","licence":"Data © OpenStreetMap contributors, ODbL 1.0. https:\/\/osm.org\/copyright","osm_type":"way","osm_id":"191508282","lat":"54.3004150140189","lon":"-9.39993720828084","display_name":"R314, Doonfeeny Lower, Ballycastle ED, Ballina, County Mayo, Connacht, Ireland","address":{"country":"Ireland","country_code":"ie"},"boundingbox":["54.2964027","54.3045856","-9.4337961","-9.3960403"]}
        JSON;
        // phpcs:enable Generic.Files.LineLength.TooLong
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $json),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $this->app->instance(Client::class, $client);

        $note = new Note();
        $address = $note->reverseGeoCode(54.3, 9.4);

        $this->assertEquals('<span class="p-country-name">Ireland</span>', $address);
    }

    /** @test */
    public function addImageElementToNoteContentWhenMediaAssociated(): void
    {
        $media = new Media([
            'type' => 'image',
            'path' => 'test.png',
        ]);
        $media->save();
        $note = new Note(['note' => 'A nice image']);
        $note->save();
        $note->media()->save($media);

        $expected = "<p>A nice image</p>
<img src=\"" . config('filesystems.disks.s3.url') . "/test.png\" alt=\"\">";
        $this->assertEquals($expected, $note->content);
    }

    /** @test */
    public function addVideoElementToNoteContentWhenMediaAssociated(): void
    {
        $media = new Media([
            'type' => 'video',
            'path' => 'test.mkv',
        ]);
        $media->save();
        $note = new Note(['note' => 'A nice video']);
        $note->save();
        $note->media()->save($media);

        $expected = "<p>A nice video</p>
<video src=\"" . config('filesystems.disks.s3.url') . "/test.mkv\">";
        $this->assertEquals($expected, $note->content);
    }

    /** @test */
    public function addAudioElementToNoteContentWhenMediaAssociated(): void
    {
        $media = new Media([
            'type' => 'audio',
            'path' => 'test.flac',
        ]);
        $media->save();
        $note = new Note(['note' => 'Some nice audio']);
        $note->save();
        $note->media()->save($media);

        $expected = "<p>Some nice audio</p>
<audio src=\"" . config('filesystems.disks.s3.url') . "/test.flac\">";
        $this->assertEquals($expected, $note->content);
    }

    /**
     * @test
     * @todo Why do we need to provide text?
     */
    public function provideTextForBlankContent(): void
    {
        $note = new Note();

        $this->assertEquals('A blank note', $note->content);
    }

    // @todo Sort out twitter requests
    /** @test
    public function setTwitterContentToNullWhenOembedErrorOccurs(): void
    {
        $note = new Note();
        $note->in_reply_to = 'https://twitter.com/search';

        $this->assertNull($note->twitter);
    }*/

    /** @test */
    public function markdownContentGetsConverted(): void
    {
        $note = Note::create([
            'note' => 'The best search engine? https://duckduckgo.com',
        ]);

        $this->assertSame(
            '<p>The best search engine? <a href="https://duckduckgo.com">https://duckduckgo.com</a></p>' . PHP_EOL,
            $note->note
        );
    }

    /**
     * For now, just reply on a cached object instead of actually querying Twitter.
     *
     * @test
     */
    public function checkInReplyToIsTwitterLink(): void
    {
        $tempContent = (object) [
            'html' => 'something random',
        ];
        Cache::put('933662564587855877', $tempContent);

        $note = Note::find(1);

        $this->assertSame($tempContent, $note->twitter);
    }

    /** @test */
    public function latitudeCanBeParsedFromPlainLocation(): void
    {
        $note = Note::find(18);

        $this->assertSame(1.23, $note->latitude);
    }

    /** @test */
    public function longitudeCanBeParsedFromPlainLocation(): void
    {
        $note = Note::find(18);

        $this->assertSame(4.56, $note->longitude);
    }

    /** @test */
    public function addressAttributeCanBeRetrievedFromPlainLocation(): void
    {
        Cache::put('1.23,4.56', '<span class="p-country-name">Antarctica</span>');

        $note = Note::find(18);

        $this->assertSame('<span class="p-country-name">Antarctica</span>', $note->address);
    }
}
