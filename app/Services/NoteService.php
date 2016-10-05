<?php

namespace App\Services;

use App\Note;
use App\Place;
use Illuminate\Http\Request;
use App\Jobs\SendWebMentions;
use App\Jobs\SyndicateToTwitter;

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
            $place = $request->input('properties.location');
            if (is_array($place)) {
                $place = $place[0];
            }
        } else {
            $content = $request->input('content');
            $inReplyTo = $request->input('in-reply-to');
            $place = $request->input('location');
        }

        $note = Note::create(
            [
                'note' => $content,
                'in_reply_to' => $inReplyTo,
                'client_id' => $clientId,
            ]
        );

        if ($place !== null && $place !== 'no-location') {
            if (substr($place, 0, strlen(config('app.url'))) == config('app.url')) {
                //uri of form http://host/place/slug, we want slug so chop off start
                //that’s the app’s url plus `/place/`
                $slug = mb_substr($place, mb_strlen(config('app.url')) + 7);
                $placeModel = Place::where('slug', '=', $slug)->first();
                $note->place()->associate($placeModel);
                $note->save();
            }
            if (substr($place, 0, 4) == 'geo:') {
                preg_match_all(
                    '/([0-9\.\-]+)/',
                    $place,
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
                $note->addMedia($file)->toMediaLibrary('images', 's3');
            }
        }

        dispatch(new SendWebMentions($note));

        if (//micropub request, syndication sent as array
            (is_array($request->input('syndicate-to'))
                &&
            (in_array('https://twitter.com/jonnybarnes', $request->input('syndicate-to')))
            || //micropub request, syndication sent as string
            ($request->input('syndicate-to') == 'https://twitter.com/jonnybarnes')
            || //local admin cp request
            ($request->input('twitter') == true))
        ) {
            dispatch(new SyndicateToTwitter($note));
        }

        return $note;
    }
}
