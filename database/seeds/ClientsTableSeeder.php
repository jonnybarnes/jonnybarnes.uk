<?php

use Illuminate\Database\Seeder;

class ClientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('clients')->insert([
            'client_url' => 'https://jbl5.dev/notes/new',
            'client_name' => 'JBL5',
            'created_at' => '2016-01-12 16:03:00',
            'updated_at' => '2016-01-12 16:03:00',
        ]);
    }
}
