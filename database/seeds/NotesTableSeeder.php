<?php

use Illuminate\Database\Seeder;

class NotesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Note::class, 10)->create();
        sleep(1);
        $noteWithPlace = App\Note::create([
            'note' => 'Having a #beer at the local. 🍺',
        ]);
        $noteWithPlace->tweet_id = '123456789';
        $place = App\Place::find(1);
        $noteWithPlace->place()->associate($place);
        $noteWithPlace->save();
        sleep(1);
        $noteWithContact = App\Note::create([
            'note' => 'Hi @tantek'
        ]);
        sleep(1);
        $noteWithContactPlusPic = App\Note::create([
            'note' => 'Hi @aaron',
            'client_id' => 'https://jbl5.dev/notes/new'
        ]);
        sleep(1);
        $noteWithoutContact = App\Note::create([
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
        $noteWithCoords = App\Note::create([
            'note' => 'Note from somehwere',
        ]);
        $noteWithCoords->location = '53.499,-2.379';
        $noteWithCoords->save();
        sleep(1);
        $noteSyndicated = App\Note::create([
            'note' => 'This note has all the syndication targets',
        ]);
        $noteSyndicated->tweet_id = '123456';
        $noteSyndicated->facebook_url = 'https://www.facebook.com/post/12345789';
        $noteSyndicated->swarm_url = 'https://www.swarmapp.com/checking/123456789';
        $noteSyndicated->instagram_url = 'https://www.instagram.com/p/aWsEd123Jh';
        $noteSyndicated->save();
    }
}
