<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Seed the users table.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'jonny',
            'password' => bcrypt('password'),
        ]);
    }
}
