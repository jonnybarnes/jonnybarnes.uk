<?php

namespace App\Services;

use App\Note;
use App\Place;
use Illuminate\Http\Request;
use App\Jobs\SendWebMentions;
use App\Jobs\SyndicateToTwitter;
use App\Jobs\SyndicateToFacebook;

class NoteService
{
    /**
     * Create a new note.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string $clientId
     * @return \App\Note $note
     */
    public function createNote(Request $request, $clientId = null)
    {
        if ($request->header('Content-Type') == 'application/json') {
            $content = $request->input('properties.content')[0];
            $inReplyTo = $request->input('properties.in-reply-to')[0];
            $location = $request->input('properties.location');
            if (is_array($location)) {
                $location = $location[0];
            }
        } else {
            $content = $request->input('content');
            $inReplyTo = $request->input('in-reply-to');
            $location = $request->input('location');
        }

        $note = Note::create(
            [
                'note' => $content,
                'in_reply_to' => $inReplyTo,
                'client_id' => $clientId,
            ]
        );

        if ($location !== null && $location !== 'no-location') {
            if (substr($location, 0, strlen(config('app.url'))) == config('app.url')) {
                //uri of form http://host/places/slug, we want slug so chop off start
                //that’s the app’s url plus `/places/`
                $slug = mb_substr($location, mb_strlen(config('app.url')) + 8);
                $place = Place::where('slug', '=', $slug)->first();
                $note->place()->associate($place);
                $note->save();
            }
            if (substr($location, 0, 4) == 'geo:') {
                preg_match_all(
                    '/([0-9\.\-]+)/',
                    $location,
                    $matches
                );
                $note->location = $matches[0][0] . ', ' . $matches[0][1];
                $note->save();
            }
        }

        //add images to media library
        if ($request->hasFile('photo')) {
            $files = $request->file('photo');
            foreach ($files as $file) {
                $note->addMedia($file)->toCollectionOnDisk('images', 's3');
            }
        }

        dispatch(new SendWebMentions($note));

        //syndication targets
        //from admin CP
        if ($request->input('twitter')) {
            dispatch(new SyndicateToTwitter($note));
        }
        if ($request->input('facebook')) {
            dispatch(new SyndicateToFacebook($note));
        }
        //from a micropub request
        $targets = array_pluck(config('syndication.targets'), 'uid', 'service.name');
        if (is_string($request->input('mp-syndicate-to'))) {
            $service = array_search($request->input('mp-syndicate-to'));
            if ($service == 'Twitter') {
                dispatch(new SyndicateToTwitter($note));
            }
            if ($service == 'Facebook') {
                dispatch(new SyndicateToFacebook($note));
            }
        }
        if (is_array($request->input('mp-syndicate-to'))) {
            foreach ($targets as $service => $target) {
                if (in_array($target, $request->input('mp-syndicate-to'))) {
                    if ($service == 'Twitter') {
                        dispatch(new SyndicateToTwitter($note));
                    }
                    if ($service == 'Facebook') {
                        dispatch(new SyndicateToFacebook($note));
                    }
                }
            }
        }

        return $note;
    }
}
