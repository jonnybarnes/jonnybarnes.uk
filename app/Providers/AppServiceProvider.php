<?php

namespace App\Providers;

use App\Models\Note;
use Codebird\Codebird;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Observers\NoteObserver;
use Laravel\Dusk\DuskServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Note::observe(NoteObserver::class);

        // Request AS macro
        Request::macro('wantsActivityStream', function () {
            return Str::contains(mb_strtolower($this->header('Accept')), 'application/activity+json');
        });

        // configure Intervention/Image
        $this->app->bind('Intervention\Image\ImageManager', function () {
            return new \Intervention\Image\ImageManager(['driver' => config('image.driver')]);
        });

        // Bind the Codebird client
        $this->app->bind('Codebird\Codebird', function () {
            Codebird::setConsumerKey(
                env('TWITTER_CONSUMER_KEY'),
                env('TWITTER_CONSUMER_SECRET')
            );

            $cb = Codebird::getInstance();

            $cb->setToken(
                env('TWITTER_ACCESS_TOKEN'),
                env('TWITTER_ACCESS_TOKEN_SECRET')
            );

            return $cb;
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local', 'testing')) {
            $this->app->register(DuskServiceProvider::class);
        }
    }
}
