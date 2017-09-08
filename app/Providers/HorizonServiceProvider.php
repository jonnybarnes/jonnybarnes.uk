<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Laravel\Horizon\Horizon;
use Illuminate\Support\ServiceProvider;

class HorizonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Horizon::auth(function (Request $request) {
            // return true/false
            if (app()->environment('production') !== true) {
                // we aren’t live so just let us into Horizon
                return true;
            }
            if ($request->session()->has('loggedin')) {
                // are we logged in as an authed user
                return $request->session()->get('loggedin');
            }

            return false;
        });
    }
}
