<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Note;
use Illuminate\Http\JsonResponse;

class FeedsController extends Controller
{
    /**
     * Returns the blog JSON feed.
     */
    public function blogJson(): JsonResponse
    {
        $articles = Article::where('published', '1')->latest('updated_at')->take(20)->get();
        $data = [
            'version' => 'https://jsonfeed.org/version/1.1',
            'title' => 'The JSON Feed for '.config('user.display_name').'’s blog',
            'home_page_url' => config('app.url').'/blog',
            'feed_url' => config('app.url').'/blog/feed.json',
            'authors' => [
                [
                    'name' => config('user.display_name'),
                    'url' => config('app.url'),
                ],
            ],
            'items' => [],
        ];

        foreach ($articles as $key => $article) {
            $data['items'][$key] = [
                'id' => config('app.url').$article->link,
                'title' => $article->title,
                'url' => config('app.url').$article->link,
                'content_html' => $article->main,
                'date_published' => $article->created_at->tz('UTC')->toRfc3339String(),
                'date_modified' => $article->updated_at->tz('UTC')->toRfc3339String(),
            ];
        }

        return response()->json($data, 200, [
            'Content-Type' => 'application/feed+json',
        ]);
    }

    /**
     * Returns the notes JSON feed.
     */
    public function notesJson(): JsonResponse
    {
        $notes = Note::latest()->with('media', 'place', 'tags')->take(20)->get();
        $data = [
            'version' => 'https://jsonfeed.org/version/1.1',
            'title' => 'The JSON Feed for '.config('user.display_name').'’s notes',
            'home_page_url' => config('app.url').'/notes',
            'feed_url' => config('app.url').'/notes/feed.json',
            'authors' => [
                [
                    'name' => config('user.display_name'),
                    'url' => config('app.url'),
                ],
            ],
            'items' => [],
        ];

        foreach ($notes as $key => $note) {
            $data['items'][$key] = [
                'id' => $note->uri,
                'url' => $note->uri,
                'content_text' => $note->content,
                'date_published' => $note->created_at->tz('UTC')->toRfc3339String(),
                'date_modified' => $note->updated_at->tz('UTC')->toRfc3339String(),
            ];
            if ($note->tags->count() > 0) {
                $data['items'][$key]['tags'] = implode(',', $note->tags->pluck('tag')->toArray());
            }
        }

        return response()->json($data, 200, [
            'Content-Type' => 'application/feed+json',
        ]);
    }

    /**
     * Returns the blog JF2 feed.
     */
    public function blogJf2(): JsonResponse
    {
        $articles = Article::where('published', '1')->latest('updated_at')->take(20)->get();
        $items = [];
        foreach ($articles as $article) {
            $items[] = [
                'type' => 'entry',
                'published' => $article->created_at,
                'uid' => config('app.url').$article->link,
                'url' => config('app.url').$article->link,
                'content' => [
                    'text' => $article->main,
                    'html' => $article->html,
                ],
                'post-type' => 'article',
            ];
        }

        return response()->json([
            'type' => 'feed',
            'name' => 'Blog feed for '.config('app.name'),
            'url' => url('/blog'),
            'author' => [
                'type' => 'card',
                'name' => config('user.display_name'),
                'url' => config('app.url'),
            ],
            'children' => $items,
        ], 200, [
            'Content-Type' => 'application/jf2feed+json',
        ]);
    }

    /**
     * Returns the notes JF2 feed.
     */
    public function notesJf2(): JsonResponse
    {
        $notes = Note::latest()->take(20)->get();
        $items = [];
        foreach ($notes as $note) {
            $items[] = [
                'type' => 'entry',
                'published' => $note->created_at,
                'uid' => $note->uri,
                'url' => $note->uri,
                'content' => [
                    'text' => $note->getRawOriginal('note'),
                    'html' => $note->note,
                ],
                'post-type' => 'note',
            ];
        }

        return response()->json([
            'type' => 'feed',
            'name' => 'Notes feed for '.config('app.name'),
            'url' => url('/notes'),
            'author' => [
                'type' => 'card',
                'name' => config('user.display_name'),
                'url' => config('app.url'),
            ],
            'children' => $items,
        ], 200, [
            'Content-Type' => 'application/jf2feed+json',
        ]);
    }
}
