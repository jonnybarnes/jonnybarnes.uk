<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bio;
use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Note;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * @psalm-suppress UnusedClass
 */
class FrontPageController extends Controller
{
    /**
     * Show all the recent activity.
     */
    public function index(): Response|View
    {
        $notes = Note::latest()->with(['media', 'client', 'place'])->withCount(['webmentions AS replies' => function ($query) {
            $query->where('type', 'in-reply-to');
        }])
        ->withCount(['webmentions AS likes' => function ($query) {
            $query->where('type', 'like-of');
        }])
        ->withCount(['webmentions AS reposts' => function ($query) {
            $query->where('type', 'repost-of');
        }])->get();
        $articles = Article::latest()->get();
        $bookmarks = Bookmark::latest()->with('tags')->get();
        $likes = Like::latest()->get();

        $items = collect($notes)
            ->merge($articles)
            ->merge($bookmarks)
            ->merge($likes)
            ->sortByDesc('updated_at')
            ->paginate(10);

        $bio = Bio::first()?->content;

        return view('front-page', [
            'items' => $items,
            'bio' => $bio,
        ]);
    }
}
