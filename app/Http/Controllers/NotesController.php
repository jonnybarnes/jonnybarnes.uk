<?php

namespace App\Http\Controllers;

use App\Note;
use Illuminate\Http\Request;
use Jonnybarnes\IndieWeb\Numbers;

// Need to sort out Twitter and webmentions!

class NotesController extends Controller
{
    /**
     * Show all the notes.
     *
     * @param  Illuminate\Http\Request request;
     * @return \Illuminte\View\Factory view
     */
    public function index(Request $request)
    {
        $notes = Note::orderBy('id', 'desc')
            ->with('place', 'media', 'client')
            ->withCount(['webmentions As replies' => function ($query) {
                $query->where('type', 'in-reply-to');
            }])->paginate(10);

        $homepage = ($request->path() == '/');

        return view('notes.index', compact('notes', 'homepage'));
    }

    /**
     * Show a single note.
     *
     * @param  string The id of the note
     * @return \Illuminate\View\Factory view
     */
    public function show($urlId)
    {
        $note = Note::nb60($urlId)->first();
        $replies = [];
        $reposts = [];
        $likes = [];
        foreach ($note->webmentions as $webmention) {
            $content['author'] = $webmention->author;
            $content['published'] = $webmention->published;
            $content['source'] = $webmention->source;
            switch ($webmention->type) {
                case 'in-reply-to':
                    $content['reply'] = $webmention->reply;
                    $microformats = json_decode($webmention->mf2, true);
                    $content['reply'] = $this->filterHTML(
                        $microformats['items'][0]['properties']['content'][0]['html']
                    );
                    $replies[] = $content;
                    break;

                case 'repost-of':
                    $reposts[] = $content;
                    break;

                case 'like-of':
                    $likes[] = $content;
                    break;
            }
        }

        return view('notes.show', compact('note', 'replies', 'reposts', 'likes'));
    }

    /**
     * Redirect /note/{decID} to /notes/{nb60id}.
     *
     * @param  string The decimal id of he note
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function redirect($decId)
    {
        $numbers = new Numbers();
        $realId = $numbers->numto60($decId);

        $url = config('app.url') . '/notes/' . $realId;

        return redirect($url);
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
