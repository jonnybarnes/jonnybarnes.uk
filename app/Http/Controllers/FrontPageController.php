<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Note;
use App\Services\ActivityStreamsService;

class FrontPageController extends Controller
{
    /**
     * Show all the recent activity.
     */
    public function index()
    {
        if (request()->wantsActivityStream()) {
            return (new ActivityStreamsService())->siteOwnerResponse();
        }

        $pageNumber = request()->query('page') ?? 1;

        $notes = Note::latest()->get();
        $articles = Article::latest()->get();
        $bookmarks = Bookmark::latest()->get();
        $likes = Like::latest()->get();

        $allItems = collect($notes)
            ->merge($articles)
            ->merge($bookmarks)
            ->merge($likes)
            ->sortByDesc('updated_at');

        $lastPage = intval(floor($allItems->count() / 10)) + 1;

        $items = $allItems->forPage($pageNumber, 10);

        if ($items->count() === 0) {
            abort(404);
        }

        $prevLink = ($pageNumber > 1) ? '/?page=' . ($pageNumber - 1) : null;
        $nextLink = ($pageNumber < $lastPage) ? '/?page=' . ($pageNumber + 1) : null;

        return view('front-page', [
            'items' => $items,
            'prevLink' => $prevLink,
            'nextLink' => $nextLink,
        ]);
    }
}
