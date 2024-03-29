<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Jonnybarnes\IndieWeb\Numbers;

/**
 * @todo Need to sort out Twitter and webmentions!
 *
 * @psalm-suppress UnusedClass
 */
class NotesController extends Controller
{
    /**
     * Show all the notes. This is also the homepage.
     */
    public function index(): View|Response
    {
        $notes = Note::latest()
            ->with('place', 'media', 'client')
            ->withCount(['webmentions AS replies' => function ($query) {
                $query->where('type', 'in-reply-to');
            }])
            ->withCount(['webmentions AS likes' => function ($query) {
                $query->where('type', 'like-of');
            }])
            ->withCount(['webmentions AS reposts' => function ($query) {
                $query->where('type', 'repost-of');
            }])->paginate(10);

        return view('notes.index', compact('notes'));
    }

    /**
     * Show a single note.
     */
    public function show(string $urlId): View|JsonResponse|Response
    {
        try {
            $note = Note::nb60($urlId)->with('place', 'media', 'client')
                ->withCount(['webmentions AS replies' => function ($query) {
                    $query->where('type', 'in-reply-to');
                }])
                ->withCount(['webmentions AS likes' => function ($query) {
                    $query->where('type', 'like-of');
                }])
                ->withCount(['webmentions AS reposts' => function ($query) {
                    $query->where('type', 'repost-of');
                }])->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            abort(404);
        }

        return view('notes.show', compact('note'));
    }

    /**
     * Redirect /note/{decID} to /notes/{nb60id}.
     */
    public function redirect(int $decId): RedirectResponse
    {
        return redirect(config('app.url') . '/notes/' . (new Numbers())->numto60($decId));
    }

    /**
     * Show all notes tagged with {tag}.
     */
    public function tagged(string $tag): View
    {
        $notes = Note::whereHas('tags', function ($query) use ($tag) {
            $query->where('tag', $tag);
        })->get();

        return view('notes.tagged', compact('notes', 'tag'));
    }

    /**
     * Page to create a new note.
     *
     * Dummy page for now.
     */
    public function create(): View
    {
        return view('notes.create');
    }
}
