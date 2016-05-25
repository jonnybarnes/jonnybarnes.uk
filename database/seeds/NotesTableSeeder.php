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
        $noteWithPlace = App\Note::create([
            'note' => 'Having a #beer at the local.'
        ]);
        $place = App\Place::find(1);
        $noteWithPlace->place()->associate($place);
        $noteWithPlace->save();
        $noteWithContact = App\Note::create([
            'note' => 'Hi @tantek'
        ]);
        $noteWithContactPlusPic = App\Note::create([
            'note' => 'Hi @aaron',
            'client_id' => 'https://jbl5.dev/notes/new'
        ]);
        $noteWithoutContact = App\Note::create([
            'note' => 'Hi @bob',
            'client_id' => 'https://quill.p3k.io'
        ]);
        //copy aaron’s profile pic in place
        $spl = new SplFileInfo(public_path() . '/assets/profile-images/aaronparecki.com');
        if ($spl->isDir() === false) {
            mkdir(public_path() . '/assets/profile-images/aaronparecki.com', 0755);
            copy(base_path() . '/tests/aaron.png', public_path() . '/assets/profile-images/aaronparecki.com/image');
        }
    }
}
