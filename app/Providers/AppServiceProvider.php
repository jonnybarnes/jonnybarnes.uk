<?php

namespace App\Providers;

use App\Tag;
use App\Note;
use Validator;
use Jonnybarnes\IndieWeb\NotePrep;
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
            $noteprep = new NotePrep();
            $tagsToAdd = [];
            $tags = $noteprep->getTags($note->note);
            foreach ($tags as $text) {
                $tag = Tag::firstOrCreate(['tag' => $text]);
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
        //
    }
}
