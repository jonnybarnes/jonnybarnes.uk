<?php

namespace App\Services;

use App\Note;
use App\Place;
use Illuminate\Http\Request;
use App\Jobs\SyndicateToTwitter;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Http\Controllers\WebMentionsController;

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
        $note = Note::create(
            [
                'note' => $request->input('content'),
                'in_reply_to' => $request->input('in-reply-to'),
                'client_id' => $clientId,
            ]
        );

        $placeSlug = $request->input('location');
        if ($placeSlug !== null && $placeSlug !== 'no-location') {
            $place = Place::where('slug', '=', $placeSlug)->first();
            $note->place()->associate($place);
            $note->save();
        }

        //add images to media library
        if ($request->hasFile('photo')) {
            $files = $request->file('photo');
            foreach ($files as $file) {
                $note->addMedia($file)->toMediaLibraryOnDisk('images', 's3');
            }
        }

        if ($request->input('webmentions')) {
            $wmc = new WebMentionsController();
            $wmc->send($note);
        }

        if (//micropub request, syndication sent as array
            (is_array($request->input('mp-syndicate-to'))
                &&
            (in_array('twitter.com/jonnybarnes', $request->input('mp-syndicate-to')))
            || //micropub request, syndication sent as string
            ($request->input('mp-syndicate-to') == 'twitter.com/jonnybarnes')
            || //local admin cp request
            ($request->input('twitter') == true))
        ) {
            $this->dispatch(new SyndicateToTwitter($note));
        }

        return $note;
    }
}
