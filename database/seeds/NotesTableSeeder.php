<?php

use Illuminate\Database\Seeder;
use App\Models\{Media, Note, Place};

class NotesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Note::class, 10)->create();
        sleep(1);
        $noteTwitterReply = Note::create([
            'note' => 'What does this even mean?',
            'in_reply_to' => 'https://twitter.com/realDonaldTrump/status/933662564587855877',
        ]);
        sleep(1);
        $noteWithPlace = Note::create([
            'note' => 'Having a #beer at the local. 🍺',
        ]);
        $noteWithPlace->tweet_id = '123456789';
        $place = Place::find(1);
        $noteWithPlace->place()->associate($place);
        $noteWithPlace->save();
        sleep(1);
        $noteWithContact = Note::create([
            'note' => 'Hi @tantek'
        ]);
        sleep(1);
        $noteWithContactPlusPic = Note::create([
            'note' => 'Hi @aaron',
            'client_id' => 'https://jbl5.dev/notes/new'
        ]);
        sleep(1);
        $noteWithoutContact = Note::create([
            'note' => 'Hi @bob',
            'client_id' => 'https://quill.p3k.io'
        ]);
        sleep(1);
        //copy aaron’s profile pic in place
        $spl = new SplFileInfo(public_path() . '/assets/profile-images/aaronparecki.com');
        if ($spl->isDir() === false) {
            mkdir(public_path() . '/assets/profile-images/aaronparecki.com', 0755);
            copy(base_path() . '/tests/aaron.png', public_path() . '/assets/profile-images/aaronparecki.com/image');
        }
        $noteWithCoords = Note::create([
            'note' => 'Note from a town',
        ]);
        $noteWithCoords->location = '53.499,-2.379';
        $noteWithCoords->save();
        sleep(1);
        $noteWithCoords2 = Note::create([
            'note' => 'Note from a city',
        ]);
        $noteWithCoords2->location = '53.9026894,-2.42250444118781';
        $noteWithCoords2->save();
        sleep(1);
        $noteWithCoords3 = Note::create([
            'note' => 'Note from a county',
        ]);
        $noteWithCoords3->location = '57.5066357,-5.0038367';
        $noteWithCoords3->save();
        sleep(1);
        $noteWithCoords4 = Note::create([
            'note' => 'Note from a country',
        ]);
        $noteWithCoords4->location = '63.000147,-136.002502';
        $noteWithCoords4->save();
        sleep(1);
        $noteSyndicated = Note::create([
            'note' => 'This note has all the syndication targets',
        ]);
        $noteSyndicated->tweet_id = '123456';
        $noteSyndicated->facebook_url = 'https://www.facebook.com/post/12345789';
        $noteSyndicated->swarm_url = 'https://www.swarmapp.com/checking/123456789';
        $noteSyndicated->instagram_url = 'https://www.instagram.com/p/aWsEd123Jh';
        $noteSyndicated->save();
        sleep(1);
        $noteWithTextLinkandEmoji = Note::create([
            'note' => 'I love https://duckduckgo.com 💕' // theres a two-heart emoji at the end of this
        ]);
        sleep(1);
        $noteJustCheckin = new Note();
        $place = Place::find(1);
        $noteJustCheckin->place()->associate($place);
        $noteJustCheckin->save();
        sleep(1);
        $media = new Media();
        $media->path = 'media/f1bc8faa-1a8f-45b8-a9b1-57282fa73f87.jpg';
        $media->type = 'image';
        $media->image_widths = '3648';
        $media->save();
        $noteWithOnlyImage = new Note();
        $noteWithOnlyImage->save();
        $noteWithOnlyImage->media()->save($media);
        sleep(1);
        $noteFromInstagram = Note::create([
            'note' => 'Lovely #wedding #weddingfavour',
        ]);
        $noteFromInstagram->instagram_url = 'https://www.instagram.com/p/Bbo22MHhE_0';
        $noteFromInstagram->save();
        $mediaInstagram = new Media();
        $mediaInstagram->path = 'https://scontent-lhr3-1.cdninstagram.com/t51.2885-15/e35/23734479_149605352435937_400133507076063232_n.jpg';
        $mediaInstagram->type = 'image';
        $mediaInstagram->save();
        $noteFromInstagram->media()->save($mediaInstagram);
        sleep(1);
        $noteCapitalHashtag = Note::create([
            'note' => 'A #TwoWord hashtag',
        ]);
    }
}
