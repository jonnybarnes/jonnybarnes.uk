<?php

use App\Contact;
use Illuminate\Database\Seeder;

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
            'twitter' => 'aaronpk',
            'facebook' => '123456',
        ]);
    }
}
