<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\{Article, Note};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class FeedsController extends Controller
{
    /**
     * Returns the blog RSS feed.
     *
     * @return Response
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
     * @return Response
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
     * @return Response
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
     * @return Response
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
     * @return array
     */
    public function blogJson(): array
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
     * @return array
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

    /**
     * Returns the blog JF2 feed.
     *
     * @return JsonResponse
     */
    public function blogJf2(): JsonResponse
    {
        $articles = Article::where('published', '1')->latest('updated_at')->take(20)->get();
        $items = [];
        foreach ($articles as $article) {
            $items[] = [
                'type' => 'entry',
                'published' => $article->created_at,
                'uid' => config('app.url') . $article->link,
                'url' => config('app.url') . $article->link,
                'content' => [
                    'text' => $article->main,
                    'html' => $article->html,
                ],
                'post-type' => 'article',
            ];
        }

        return response()->json([
            'type' => 'feed',
            'name' => 'Blog feed for ' . config('app.name'),
            'url' => url('/blog'),
            'author' => [
                'type' => 'card',
                'name' => config('user.displayname'),
                'url' => config('app.longurl'),
            ],
            'children' => $items,
        ], 200, [
            'Content-Type' => 'application/jf2feed+json',
        ]);
    }

    /**
     * Returns the notes JF2 feed.
     *
     * @return JsonResponse
     */
    public function notesJf2(): JsonResponse
    {
        $notes = Note::latest()->take(20)->get();
        $items = [];
        foreach ($notes as $note) {
            $items[] = [
                'type' => 'entry',
                'published' => $note->created_at,
                'uid' => $note->longurl,
                'url' => $note->longurl,
                'content' => [
                    'text' => $note->getRawOriginal('note'),
                    'html' => $note->note,
                ],
                'post-type' => 'note',
            ];
        }

        return response()->json([
            'type' => 'feed',
            'name' => 'Notes feed for ' . config('app.name'),
            'url' => url('/notes'),
            'author' => [
                'type' => 'card',
                'name' => config('user.displayname'),
                'url' => config('app.longurl'),
            ],
            'children' => $items,
        ], 200, [
            'Content-Type' => 'application/jf2feed+json',
        ]);
    }
}
