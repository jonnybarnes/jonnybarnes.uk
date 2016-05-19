<?php

use Illuminate\Database\Seeder;

class PlacesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('places')->insert([
            'name' => 'The Bridgewater Pub',
            'slug' => 'the-bridgewater-pub',
            'description' => 'A lovely local pub with a decent selection pf cask ales',
            'location' => 'POINT(-2.3805 53.4983)',
            'created_at' => '2016-01-12 16:19:00',
            'updated_at' => '2016-01-12 16:19:00',
        ]);
    }
}
