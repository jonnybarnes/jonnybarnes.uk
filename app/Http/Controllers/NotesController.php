<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Jonnybarnes\IndieWeb\Numbers;
use App\Services\ActivityStreamsService;

// Need to sort out Twitter and webmentions!

class NotesController extends Controller
{
    /**
     * Show all the notes. This is also the homepage.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
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

        return view('notes.index', compact('notes'));
    }

    /**
     * Show a single note.
     *
     * @param  string  $urlId The id of the note
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(string $urlId)
    {
        $note = Note::nb60($urlId)->with('webmentions')->firstOrFail();

        if (request()->wantsActivityStream()) {
            return (new ActivityStreamsService)->singleNoteResponse($note);
        }

        return view('notes.show', compact('note'));
    }

    /**
     * Redirect /note/{decID} to /notes/{nb60id}.
     *
     * @param  int  $decId The decimal id of the note
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function redirect(int $decId)
    {
        return redirect(config('app.url') . '/notes/' . (new Numbers())->numto60($decId));
    }

    /**
     * Show all notes tagged with {tag}.
     *
     * @param  string  $tag
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function tagged(string $tag)
    {
        $notes = Note::whereHas('tags', function ($query) use ($tag) {
            $query->where('tag', $tag);
        })->get();

        return view('notes.tagged', compact('notes', 'tag'));
    }
}
