<?php

namespace App\Http\Controllers;

use App\Note;
use Illuminate\Http\Request;
use Jonnybarnes\IndieWeb\Numbers;
use App\Services\ActivityStreamsService;

// Need to sort out Twitter and webmentions!

class NotesController extends Controller
{
    /**
     * Show all the notes. This is also the homepage.
     *
     * @return \Illuminte\View\Factory view
     */
    public function index()
    {
        if (request()->wantsActivityStream()) {
            return (new ActivityStreamsService)->siteOwnerResponse();
        }

        $notes = Note::latest()
            ->with('place', 'media', 'client')
            ->withCount(['webmentions As replies' => function ($query) {
                $query->where('type', 'in-reply-to');
            }])->paginate(10);
        $aslink = config('app.url');

        return view('notes.index', compact('notes', 'aslink'));
    }

    /**
     * Show a single note.
     *
     * @param  string The id of the note
     * @return \Illuminate\View\Factory view
     */
    public function show($urlId)
    {
        $note = Note::nb60($urlId)->with('webmentions')->firstOrFail();

        if (request()->wantsActivityStream()) {
            return (new ActivityStreamsService)->singleNoteResponse($note);
        }

        $aslink = $note->longurl;

        return view('notes.show', compact('note', 'aslink'));
    }

    /**
     * Redirect /note/{decID} to /notes/{nb60id}.
     *
     * @param  string The decimal id of he note
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function redirect($decId)
    {
        return redirect(config('app.url') . '/notes/' . (new Numbers())->numto60($decId));
    }

    /**
     * Show all notes tagged with {tag}.
     *
     * @param  string The tag
     * @return \Illuminate\View\Factory view
     */
    public function tagged($tag)
    {
        $notes = Note::whereHas('tags', function ($query) use ($tag) {
            $query->where('tag', $tag);
        })->get();

        return view('notes.tagged', compact('notes', 'tag'));
    }
}
