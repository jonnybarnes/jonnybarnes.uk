<?php

declare(strict_types=1);

namespace App\Services;

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

        //check the input
        if (array_key_exists('content', $data) === false) {
            $data['content'] = null;
        }
        if (array_key_exists('in-reply-to', $data) === false) {
            $data['in-reply-to'] = null;
        }
        if (array_key_exists('client-id', $data) === false) {
            $data['client-id'] = null;
        }
        $note = Note::create(
            [
                'note' => $data['content'],
                'in_reply_to' => $data['in-reply-to'],
                'client_id' => $data['client-id'],
            ]
        );

        if (array_key_exists('published', $data) && empty($data['published']) === false) {
            $carbon = carbon($data['published']);
            $note->created_at = $note->updated_at = $carbon->toDateTimeString();
        }

        if (array_key_exists('location', $data) && $data['location'] !== null && $data['location'] !== 'no-location') {
            if (starts_with($data['location'], config('app.url'))) {
                //uri of form http://host/places/slug, we want slug
                //get the URL path, then take last part, we can hack with basename
                //as path looks like file path.
                $place = Place::where('slug', basename(parse_url($data['location'], PHP_URL_PATH)))->first();
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

        if (array_key_exists('checkin', $data) && $data['checkin'] !== null) {
            $place = Place::where('slug', basename(parse_url($data['checkin'], PHP_URL_PATH)))->first();
            if ($place !== null) {
                $note->place()->associate($place);
                $note->swarm_url = $data['swarm-url'];
                if ($note->note === null || $note->note == '') {
                    $note->note = 'I’ve just checked in with Swarm';
                }
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
        if (array_key_exists('photo', $data)) {
            foreach ($data['photo'] as $photo) {
                // check the media was uploaded to my endpoint, and use path
                if (starts_with($photo, config('filesystems.disks.s3.url'))) {
                    $path = substr($photo, strlen(config('filesystems.disks.s3.url')));
                    $media = Media::where('path', ltrim($path, '/'))->firstOrFail();
                } else {
                    $media = Media::firstOrNew(['path' => $photo]);
                    // currently assuming this is a photo from Swarm or OwnYourGram
                    $media->type = 'image';
                    $media->save();
                }
                $note->media()->save($media);
            }
            if (array_key_exists('instagram-url', $data)) {
                $note->instagram_url = $data['instagram-url'];
            }
        }

        $note->save();

        dispatch(new SendWebMentions($note));

        //syndication targets
        if (in_array('twitter', $data['syndicate'])) {
            dispatch(new SyndicateNoteToTwitter($note));
        }
        if (in_array('facebook', $data['syndicate'])) {
            dispatch(new SyndicateNoteToFacebook($note));
        }

        return $note;
    }
}
