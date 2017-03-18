<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

use App\{Media, Note, Place};
use App\Jobs\{SendWebMentions, SyndicateToFacebook, SyndicateToTwitter};

class NoteService
{
    /**
     * Create a new note.
     *
     * @param  array $data
     * @return \App\Note $note
     */
    public function createNote(array $data): Note
    {
        $note = Note::create(
            [
                'note' => $data['content'],
                'in_reply_to' => $data['in-reply-to'],
                'client_id' => $data['client-id'],
            ]
        );

        if (array_key_exists('location', $data) && $data['location'] !== null && $data['location'] !== 'no-location') {
            if (substr($data['location'], 0, strlen(config('app.url'))) == config('app.url')) {
                //uri of form http://host/places/slug, we want slug so chop off start
                //that’s the app’s url plus `/places/`
                $slug = mb_substr($location, mb_strlen(config('app.url')) + 8);
                $place = Place::where('slug', '=', $slug)->first();
                $note->place()->associate($place);
            }
            if (substr($data['location'], 0, 4) == 'geo:') {
                preg_match_all(
                    '/([0-9\.\-]+)/',
                    $data['location'],
                    $matches
                );
                $note->location = $matches[0][0] . ', ' . $matches[0][1];
            }
        }

        /* drop image support for now
        //add images to media library
        if ($request->hasFile('photo')) {
            $files = $request->file('photo');
            foreach ($files as $file) {
                $note->addMedia($file)->toCollectionOnDisk('images', 's3');
            }
        }
        */
        //add support for media uploaded as URLs
        foreach ($data['photo'] as $photo) {
            // check the media was uploaded to my endpoint
            if (starts_with($photo, config('filesystems.disks.s3.url'))) {
                $path = substr($photo, strlen(config('filesystems.disks.s3.url')));
                $media = Media::where('path', ltrim($path, '/'))->firstOrFail();
                $note->media()->save($media);
            }
        }

        $note->save();

        dispatch(new SendWebMentions($note));

        //syndication targets
        if (in_array('twitter', $data['syndicate'])) {
            dispatch(new SyndicateToTwitter($note));
        }
        if (in_array('facebook', $data['syndicate'])) {
            dispatch(new SyndicateToFacebook($note));
        }

        return $note;
    }
}
