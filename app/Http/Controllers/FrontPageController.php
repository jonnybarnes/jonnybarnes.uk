<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Note;
use App\Models\Article;
use App\Models\Bookmark;

class FrontPageController extends Controller
{
    /**
     * Show all the recent activity.
     */
    public function index()
    {
        $pageNumber = request()->query('page') ?? 1;

        $notes = Note::latest()->get();
        $articles = Article::latest()->get();
        $bookmarks = Bookmark::latest()->get();
        $likes = Like::latest()->get();

        $allItems = collect($notes)
            ->merge($articles)
            ->merge($bookmarks)
            ->merge($likes)
            ->sortByDesc('updated_at')
            ->chunk(10);

        $page = $allItems->get($pageNumber - 1);

        if (is_null($page)) {
            abort(404);
        }

        return view('front-page', [
            'items' => $page,
        ]);
    }
}
