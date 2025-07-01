<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ArticlesTableSeeder::class);
        $this->call(ClientsTableSeeder::class);
        $this->call(ContactsTableSeeder::class);
        $this->call(PlacesTableSeeder::class);
        $this->call(NotesTableSeeder::class);
        $this->call(WebMentionsTableSeeder::class);
        $this->call(LikesTableSeeder::class);
        $this->call(BookmarksTableSeeder::class);
        $this->call(UsersTableSeeder::class);
    }
}
