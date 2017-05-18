<?php

use Illuminate\Database\Seeder;

class IndieWebUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        App\IndieWebUser::create(['me' => config('app.url')]);
    }
}
