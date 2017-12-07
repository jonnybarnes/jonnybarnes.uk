<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use App\{Media, Note, Place};
use App\Jobs\{SendWebMentions, SyndicateNoteToFacebook, SyndicateNoteToTwitter};

class NoteService
{
    /**
     * Create a new note.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \App\Note $note
     */
    public function createNote(Request $request): Note
    {
        //move the request to data code here before refactor
        $data = [];
        $data['client-id'] = resolve(TokenService::class)
            ->validateToken($request->bearerToken())
            ->getClaim('client_id');
        if ($request->header('Content-Type') == 'application/json') {
            if (is_string($request->input('properties.content.0'))) {
                $data['content'] = $request->input('properties.content.0'); //plaintext content
            }
            if (is_array($request->input('properties.content.0'))
                && array_key_exists('html', $request->input('properties.content.0'))
            ) {
                $data['content'] = $request->input('properties.content.0.html');
            }
            $data['in-reply-to'] = $request->input('properties.in-reply-to.0');
            // check location is geo: string
            if (is_string($request->input('properties.location.0'))) {
                $data['location'] = $request->input('properties.location.0');
            }
            // check location is h-card
            if (is_array($request->input('properties.location.0'))) {
                if ($request->input('properties.location.0.type.0' === 'h-card')) {
                    try {
                        $place = resolve(PlaceService::class)->createPlaceFromCheckin(
                            $request->input('properties.location.0')
                        );
                        $data['checkin'] = $place->longurl;
                    } catch (\Exception $e) {
                        //
                    }
                }
            }
            $data['published'] = $request->input('properties.published.0');
            //create checkin place
            if (array_key_exists('checkin', $request->input('properties'))) {
                $data['swarm-url'] = $request->input('properties.syndication.0');
                try {
                    $place = resolve(PlaceService::class)->createPlaceFromCheckin(
                        $request->input('properties.checkin.0')
                    );
                    $data['checkin'] = $place->longurl;
                } catch (\Exception $e) {
                    $data['checkin'] = null;
                    $data['swarm-url'] = null;
                }
            }
        } else {
            $data['content'] = $request->input('content');
            $data['in-reply-to'] = $request->input('in-reply-to');
            $data['location'] = $request->input('location');
            $data['published'] = $request->input('published');
        }
        $data['syndicate'] = [];
        $targets = array_pluck(config('syndication.targets'), 'uid', 'service.name');
        $mpSyndicateTo = null;
        if ($request->has('mp-syndicate-to')) {
            $mpSyndicateTo = $request->input('mp-syndicate-to');
        }
        if ($request->has('properties.mp-syndicate-to')) {
            $mpSyndicateTo = $request->input('properties.mp-syndicate-to');
        }
        if (is_string($mpSyndicateTo)) {
            $service = array_search($mpSyndicateTo, $targets);
            if ($service == 'Twitter') {
                $data['syndicate'][] = 'twitter';
            }
            if ($service == 'Facebook') {
                $data['syndicate'][] = 'facebook';
            }
        }
        if (is_array($mpSyndicateTo)) {
            foreach ($mpSyndicateTo as $uid) {
                $service = array_search($uid, $targets);
                if ($service == 'Twitter') {
                    $data['syndicate'][] = 'twitter';
                }
                if ($service == 'Facebook') {
                    $data['syndicate'][] = 'facebook';
                }
            }
        }
        $data['photo'] = [];
        $photos = null;
        if ($request->has('photo')) {
            $photos = $request->input('photo');
        }
        if ($request->has('properties.photo')) {
            $photos = $request->input('properties.photo');
        }
        if ($photos !== null) {
            foreach ($photos as $photo) {
                if (is_string($photo)) {
                    //only supporting media URLs for now
                    $data['photo'][] = $photo;
                }
            }
            if (starts_with($request->input('properties.syndication.0'), 'https://www.instagram.com')) {
                $data['instagram-url'] = $request->input('properties.syndication.0');
            }
        }
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
            $note->created_at = $note->updated_at = carbon($data['published'])
                                                          ->toDateTimeString();
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
            foreach ((array) $data['photo'] as $photo) {
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
        if (array_key_exists('syndicate', $data)) {
            if (in_array('twitter', $data['syndicate'])) {
                dispatch(new SyndicateNoteToTwitter($note));
            }
            if (in_array('facebook', $data['syndicate'])) {
                dispatch(new SyndicateNoteToFacebook($note));
            }
        }

        return $note;
    }
}
