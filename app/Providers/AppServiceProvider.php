<?php

namespace App\Providers;

use App\Tag;
use App\Note;
use Validator;
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

        //allow micropub use in development
        if (env('APP_DEBUG') == true) {
            session(['me' => 'https://jonnybarnes.localhost']);
            session(['token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZSI6Imh0dHBzOlwvXC9qb25ueWJhcm5lcy5sb2NhbGhvc3QiLCJjbGllbnRfaWQiOiJodHRwczpcL1wvam9ubnliYXJuZXMubG9jYWxob3N0XC9ub3Rlc1wvbmV3Iiwic2NvcGUiOiJwb3N0IiwiZGF0ZV9pc3N1ZWQiOjE0NzU1MTI0NDgsIm5vbmNlIjoiYzE0MzNmNzg5ZTY4Y2M1OSJ9.7Bj9yLnWJOyVre8BPihAom2G0MEsmS3tIUraDI-GRNg']);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
