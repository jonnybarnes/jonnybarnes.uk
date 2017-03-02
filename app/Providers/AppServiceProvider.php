<?php

namespace App\Providers;

use App\Tag;
use App\Note;
use Validator;
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
        // Validate photos for a maximum filesize
        Validator::extend('photosize', function ($attribute, $value, $parameters, $validator) {
            if ($value[0] !== null) {
                foreach ($value as $file) {
                    if ($file->getSize() > 5000000) {
                        return false;
                    }
                }
            }

            return true;
        });

        //Add tags for notes
        Note::created(function ($note) {
            $tagsToAdd = [];
            preg_match_all('/#([^\s<>]+)\b/', $note, $tags);
            foreach ($tags[1] as $tag) {
                $tag = Tag::normalizeTag($tag);
            }
            $tags = array_unique($tags[1]);
            foreach ($tags as $tag) {
                $tag = Tag::firstOrCreate(['tag' => $tag]);
                $tagsToAdd[] = $tag->id;
            }
            if (count($tagsToAdd > 0)) {
                $note->tags()->attach($tagsToAdd);
            }
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
