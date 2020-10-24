<?php

namespace Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Seeder;
use Illuminate\FileSystem\FileSystem;

class ContactsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Contact::create([
            'nick' => 'tantek',
            'name' => 'Tantek Çelik',
            'homepage' => 'http://tantek.com',
            'twitter' => 't',
        ]);
        Contact::create([
            'nick' => 'aaron',
            'name' => 'Aaron Parecki',
            'homepage' => 'https://aaronparecki.com',
            'facebook' => '123456',
        ]);
        $fs = new FileSystem();
        if (!$fs->exists(public_path('assets/profile-images/aaronparecki.com'))) {
            $fs->makeDirectory(public_path('assets/profile-images/aaronparecki.com'));
        }
        $fs->copy(
            base_path('tests/aaron.png'),
            public_path('assets/profile-images/aaronparecki.com/image')
        );
    }
}
