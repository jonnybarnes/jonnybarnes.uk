<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Note;
use App\Services\ActivityStreamsService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FrontPageController extends Controller
{
    /**
     * Show all the recent activity.
     *
     * @return Response|View
     */
    public function index()
    {
        if (request()->wantsActivityStream()) {
            return (new ActivityStreamsService())->siteOwnerResponse();
        }

        $notes = Note::latest()->with(['media', 'client', 'place'])->get();
        $articles = Article::latest()->get();
        $bookmarks = Bookmark::latest()->get();
        $likes = Like::latest()->get();

        $items = collect($notes)
            ->merge($articles)
            ->merge($bookmarks)
            ->merge($likes)
            ->sortByDesc('updated_at')
            ->paginate(10);

        return view('front-page', [
            'items' => $items,
        ]);
    }
}
