<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Note;
use App\Services\ActivityStreamsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Jonnybarnes\IndieWeb\Numbers;

// Need to sort out Twitter and webmentions!

class NotesController extends Controller
{
    /**
     * Show all the notes. This is also the homepage.
     *
     * @return View|Response
     */
    public function index()
    {
        if (request()->wantsActivityStream()) {
            return (new ActivityStreamsService())->siteOwnerResponse();
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
     * @param string $urlId The id of the note
     * @return View|JsonResponse|Response
     */
    public function show(string $urlId)
    {
        try {
            $note = Note::nb60($urlId)->with('webmentions')->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            abort(404);
        }

        if (request()->wantsActivityStream()) {
            return (new ActivityStreamsService())->singleNoteResponse($note);
        }

        return view('notes.show', compact('note'));
    }

    /**
     * Redirect /note/{decID} to /notes/{nb60id}.
     *
     * @param int $decId The decimal id of the note
     * @return RedirectResponse
     */
    public function redirect(int $decId): RedirectResponse
    {
        return redirect(config('app.url') . '/notes/' . (new Numbers())->numto60($decId));
    }

    /**
     * Show all notes tagged with {tag}.
     *
     * @param string $tag
     * @return View
     */
    public function tagged(string $tag): View
    {
        $notes = Note::whereHas('tags', function ($query) use ($tag) {
            $query->where('tag', $tag);
        })->get();

        return view('notes.tagged', compact('notes', 'tag'));
    }
}
