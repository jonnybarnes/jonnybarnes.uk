<?php

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
        DB::table('contacts')->insert([
            'nick' => 'tantek',
            'name' => 'Tantek Çelik',
            'homepage' => 'http://tantek.com',
            'twitter' => 't',
            'created_at' => '2016-01-12 16:11:00',
            'updated_at' => '2016-01-12 16:11:00',
        ]);

        DB::table('contacts')->insert([
            'nick' => 'aaron',
            'name' => 'Aaron Parecki',
            'homepage' => 'https://aaronparecki.com',
            'twitter' => 'aaronpk',
            'created_at' => '2016-01-12 16:12:00',
            'updated_at' => '2016-01-12 16:12:00',
        ]);
    }
}
