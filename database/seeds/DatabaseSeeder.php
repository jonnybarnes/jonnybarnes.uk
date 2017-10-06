<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ArticlesTableSeeder::class);
        $this->call(ClientsTableSeeder::class);
        $this->call(ContactsTableSeeder::class);
        $this->call(PlacesTableSeeder::class);
        $this->call(NotesTableSeeder::class);
        $this->call(WebMentionsTableSeeder::class);
        $this->call(IndieWebUserTableSeeder::class);
        $this->call(LikesTableSeeder::class);
    }
}
