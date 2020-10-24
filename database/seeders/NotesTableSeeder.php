<?php

namespace Database\Seeders;

use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Media, Note, Place};
use SplFileInfo;

class NotesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now()->subDays(rand(2, 5));
        $noteTwitterReply = Note::create([
            'note' => 'What does this even mean?',
            'in_reply_to' => 'https://twitter.com/realDonaldTrump/status/933662564587855877',
            'created_at' => $now,
        ]);
        DB::table('notes')
            ->where('id', $noteTwitterReply->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subDays(rand(2, 5));
        $noteWithPlace = Note::create([
            'note' => 'Having a #beer at the local. 🍺',
            'created_at' => $now,
        ]);
        $noteWithPlace->tweet_id = '123456789';
        $place = Place::find(1);
        $noteWithPlace->place()->associate($place);
        $noteWithPlace->save();
        DB::table('notes')
            ->where('id', $noteWithPlace->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subDays(rand(3, 6));
        $noteWithPlaceTwo = Note::create([
            'note' => 'It’s really good',
            'created_at' => $now,
        ]);
        $place = Place::find(1);
        $noteWithPlaceTwo->place()->associate($place);
        $noteWithPlaceTwo->save();
        DB::table('notes')
            ->where('id', $noteWithPlaceTwo->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subDays(rand(4, 8));
        $noteWithContact = Note::create([
            'note' => 'Hi @tantek',
            'created_at' => $now,
        ]);
        DB::table('notes')
            ->where('id', $noteWithContact->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subDays(rand(1, 10));
        $noteWithContactPlusPic = Note::create([
            'note' => 'Hi @aaron',
            'client_id' => 'https://jbl5.dev/notes/new',
            'created_at' => $now,
        ]);
        DB::table('notes')
            ->where('id', $noteWithContactPlusPic->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subDays(rand(2, 5));
        $noteWithoutContact = Note::create([
            'note' => 'Hi @bob',
            'client_id' => 'https://quill.p3k.io',
            'created_at' => $now,
        ]);
        DB::table('notes')
            ->where('id', $noteWithoutContact->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        //copy aaron’s profile pic in place
        $spl = new SplFileInfo(public_path() . '/assets/profile-images/aaronparecki.com');
        if ($spl->isDir() === false) {
            mkdir(public_path() . '/assets/profile-images/aaronparecki.com', 0755);
            copy(base_path() . '/tests/aaron.png', public_path() . '/assets/profile-images/aaronparecki.com/image');
        }

        $now = Carbon::now()->subDays(rand(3, 7));
        $noteWithCoords = Note::create([
            'note' => 'Note from a town',
            'created_at' => $now,
        ]);
        DB::table('notes')
            ->where('id', $noteWithCoords->id)
            ->update(['updated_at' => $now->toDateTimeString()]);
        $noteWithCoords->location = '53.499,-2.379';
        $noteWithCoords->save();
        $noteWithCoords2 = Note::create([
            'note' => 'Note from a city',
            'created_at' => $now,
        ]);
        $noteWithCoords2->location = '53.9026894,-2.42250444118781';
        $noteWithCoords2->save();
        DB::table('notes')
            ->where('id', $noteWithCoords2->id)
            ->update(['updated_at' => $now->toDateTimeString()]);
        $noteWithCoords3 = Note::create([
            'note' => 'Note from a county',
            'created_at' => $now,
        ]);
        $noteWithCoords3->location = '57.5066357,-5.0038367';
        $noteWithCoords3->save();
        DB::table('notes')
            ->where('id', $noteWithCoords3->id)
            ->update(['updated_at' => $now->toDateTimeString()]);
        $noteWithCoords4 = Note::create([
            'note' => 'Note from a country',
            'created_at' => $now,
        ]);
        $noteWithCoords4->location = '63.000147,-136.002502';
        $noteWithCoords4->save();
        DB::table('notes')
            ->where('id', $noteWithCoords4->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subHours(7);
        $noteSyndicated = Note::create([
            'note' => 'This note has all the syndication targets',
            'created_at' => $now,
        ]);
        $noteSyndicated->tweet_id = '123456';
        $noteSyndicated->facebook_url = 'https://www.facebook.com/post/12345789';
        $noteSyndicated->swarm_url = 'https://www.swarmapp.com/checking/123456789';
        $noteSyndicated->instagram_url = 'https://www.instagram.com/p/aWsEd123Jh';
        $noteSyndicated->save();
        DB::table('notes')
            ->where('id', $noteSyndicated->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subHours(6);
        $noteWithTextLinkandEmoji = Note::create([
            'note' => 'I love https://duckduckgo.com 💕', // there’s a two-heart emoji at the end of this
            'created_at' => $now,
        ]);
        DB::table('notes')
            ->where('id', $noteWithTextLinkandEmoji->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subHours(5);
        $noteJustCheckin = new Note();
        $noteJustCheckin->setCreatedAt($now);
        $place = Place::find(1);
        $noteJustCheckin->place()->associate($place);
        $noteJustCheckin->save();
        DB::table('notes')
            ->where('id', $noteJustCheckin->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subHours(4);
        $media = new Media();
        $media->path = 'media/f1bc8faa-1a8f-45b8-a9b1-57282fa73f87.jpg';
        $media->type = 'image';
        $media->image_widths = '3648';
        $media->save();
        $noteWithOnlyImage = new Note();
        $noteWithOnlyImage->setCreatedAt($now);
        $noteWithOnlyImage->setUpdatedAt($now);
        $noteWithOnlyImage->save();
        $noteWithOnlyImage->media()->save($media);
        DB::table('notes')
            ->where('id', $noteWithOnlyImage->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subHours(3);
        $noteCapitalHashtag = Note::create([
            'note' => 'A #TwoWord hashtag',
            'created_at' => $now,
        ]);
        DB::table('notes')
            ->where('id', $noteCapitalHashtag->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now()->subHours(2);
        $noteWithCodeContent = <<<EOF
A note with some code:
```php
<?php

echo 'Hello World';
EOF;
        $noteWithCode = Note::create([
            'note' => $noteWithCodeContent,
            'created_at' => $now,
        ]);
        DB::table('notes')
            ->where('id', $noteWithCode->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        $now = Carbon::now();
        $noteWithLongUrl = Note::create([
            'note' => 'Best site: https://example.org/posts/some-really-long-slug-that-is-too-wide-on-mobile',
            'created_at' => $now,
            'client_id' => 'https://beta.indigenous.abode.pub/ios/'
        ]);
        DB::table('notes')
            ->where('id', $noteWithLongUrl->id)
            ->update(['updated_at' => $now->toDateTimeString()]);

        Note::factory(10)->create();
    }
}
