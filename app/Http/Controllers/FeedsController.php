<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Models\{Article, Note};

class FeedsController extends Controller
{
    /**
     * Returns the blog RSS feed.
     *
     * @return \Illuminate\Http\Response
     */
    public function blogRss(): Response
    {
        $articles = Article::where('published', '1')->latest('updated_at')->take(20)->get();
        $buildDate = $articles->first()->updated_at->toRssString();

        return response()
                    ->view('articles.rss', compact('articles', 'buildDate'))
                    ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Returns the blog Atom feed.
     *
     * @return \Illuminate\Http\Response
     */
    public function blogAtom(): Response
    {
        $articles = Article::where('published', '1')->latest('updated_at')->take(20)->get();

        return response()
                    ->view('articles.atom', compact('articles'))
                    ->header('Content-Type', 'application/atom+xml; charset=utf-8');
    }

    /**
     * Returns the notes RSS feed.
     *
     * @return \Illuminate\Http\Response
     */
    public function notesRss(): Response
    {
        $notes = Note::latest()->take(20)->get();
        $buildDate = $notes->first()->updated_at->toRssString();

        return response()
                    ->view('notes.rss', compact('notes', 'buildDate'))
                    ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Returns the notes Atom feed.
     *
     * @return \Illuminate\Http\Response
     */
    public function notesAtom(): Response
    {
        $notes = Note::latest()->take(20)->get();

        return response()
                    ->view('notes.atom', compact('notes'))
                    ->header('Content-Type', 'application/atom+xml; charset=utf-8');
    }

    /** @todo sort out return type for json responses */

    /**
     * Returns the blog JSON feed.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function blogJson()
    {
        $articles = Article::where('published', '1')->latest('updated_at')->take(20)->get();
        $data = [
            'version' => 'https://jsonfeed.org/version/1',
            'title' => 'The JSON Feed for ' . config('app.display_name') . '’s blog',
            'home_page_url' => config('app.url') . '/blog',
            'feed_url' => config('app.url') . '/blog/feed.json',
            'items' => [],
        ];

        foreach ($articles as $key => $article) {
            $data['items'][$key] = [
                'id' => config('app.url') . $article->link,
                'title' => $article->title,
                'url' => config('app.url') . $article->link,
                'content_html' => $article->main,
                'date_published' => $article->created_at->tz('UTC')->toRfc3339String(),
                'date_modified' => $article->updated_at->tz('UTC')->toRfc3339String(),
                'author' => [
                    'name' => config('app.display_name'),
                ],
            ];
        }

        return $data;
    }

    /**
     * Returns the notes JSON feed.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function notesJson()
    {
        $notes = Note::latest()->take(20)->get();
        $data = [
            'version' => 'https://jsonfeed.org/version/1',
            'title' => 'The JSON Feed for ' . config('app.display_name') . '’s notes',
            'home_page_url' => config('app.url') . '/notes',
            'feed_url' => config('app.url') . '/notes/feed.json',
            'items' => [],
        ];

        foreach ($notes as $key => $note) {
            $data['items'][$key] = [
                'id' => $note->longurl,
                'url' => $note->longurl,
                'content_html' => $note->content,
                'date_published' => $note->created_at->tz('UTC')->toRfc3339String(),
                'date_modified' => $note->updated_at->tz('UTC')->toRfc3339String(),
                'author' => [
                    'name' => config('app.display_name'),
                ],
            ];
        }

        return $data;
    }
}
