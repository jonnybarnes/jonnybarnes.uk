<?php

namespace Database\Seeders;

use App\Models\MicropubClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        DB::table('clients')->insert([
            'client_url' => 'https://beta.indigenous.abode.pub/ios/',
            'client_name' => 'https://beta.indigenous.abode.pub/ios/',
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);

        MicropubClient::factory(5)->create();
    }
}
