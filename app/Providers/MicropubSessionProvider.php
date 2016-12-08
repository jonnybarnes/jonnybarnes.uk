<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class MicropubSessionProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //allow micropub use in development
        if (config('app.env') !== 'production') {
            session(['me' => env('APP_URL')]);
            if (Storage::exists('dev-token')) {
                session(['token' => Storage::get('dev-token')]);
            }
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
