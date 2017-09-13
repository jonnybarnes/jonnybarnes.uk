<?php

namespace App\Http\Controllers;

use App\Note;
use Illuminate\Http\Request;
use Jonnybarnes\IndieWeb\Numbers;

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
            $data = json_encode([
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => config('app.url'),
                'type' => 'Person',
                'name' => config('app.display_name'),
                'preferredUsername' => 'jonnybarnes',
            ]);

            return response($data)->header('Content-Type', 'application/activity+json');
        }

        $notes = Note::orderBy('id', 'desc')
            ->with('place', 'media', 'client')
            ->withCount(['webmentions As replies' => function ($query) {
                $query->where('type', 'in-reply-to');
            }])->paginate(10);

        return view('notes.index', compact('notes'));
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
            $data = json_encode([
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'summary' => 'Jonny added a note to his microblog',
                'type' => 'Add',
                'published' => $note->updated_at->toW3cString(),
                'actor' => [
                    'type' => 'Person',
                    'id' => config('app.url'),
                    'name' => config('app.display_name'),
                    'url' => config('app.url'),
                    'image' => [
                        'type' => 'Link',
                        'href' => config('app.url') . '/assets/img/jmb-bw.jpg',
                        'mediaType' => '/image/jpeg',
                    ],
                ],
                'object' => [
                    'id' => $note->longurl,
                    'type' => 'Note',
                    'url' => $note->longurl,
                    'name' => strip_tags($note->note)
                ],
            ]);

            return response($data)->header('Content-Type', 'application/activity+json');
        }

        return view('notes.show', compact('note'));
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
