<?php

namespace App\Services;

use App\Note;
use App\Place;
use Illuminate\Http\Request;
use App\Jobs\SendWebMentions;
use App\Jobs\SyndicateToTwitter;
use Illuminate\Foundation\Bus\DispatchesJobs;

class NoteService
{
    use DispatchesJobs;

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
            $placeSlug = $request->input('properties.location');
            if (is_array($placeSlug)) {
                $placeSlug = $placeSlug[0];
            }
        } else {
            $content = $request->input('content');
            $inReplyTo = $request->input('in-reply-to');
            $placeSlug = $request->input('location');
        }

        $note = Note::create(
            [
                'note' => $content,
                'in_reply_to' => $inReplyTo,
                'client_id' => $clientId,
            ]
        );

        if ($placeSlug !== null && $placeSlug !== 'no-location') {
            $place = Place::where('slug', '=', $placeSlug)->first();
            $note->place()->associate($place);
            $note->save();
        }

        //add images to media library
        if ($request->hasFile('photo')) {
            $files = $request->file('photo');
            foreach ($files as $file) {
                $note->addMedia($file)->toMediaLibrary('images', 's3');
            }
        }

        $this->dispatch(new SendWebMentions($note));

        if (//micropub request, syndication sent as array
            (is_array($request->input('syndicate-to'))
                &&
            (in_array('https://twitter.com/jonnybarnes', $request->input('syndicate-to')))
            || //micropub request, syndication sent as string
            ($request->input('syndicate-to') == 'https://twitter.com/jonnybarnes')
            || //local admin cp request
            ($request->input('twitter') == true))
        ) {
            $this->dispatch(new SyndicateToTwitter($note));
        }

        return $note;
    }
}
