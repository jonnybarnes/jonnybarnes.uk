<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bio;
use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FrontPageController extends Controller
{
    /**
     * Show all the recent activity.
     */
    public function index(Request $request): Response|View
    {
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

        $bio = Bio::first()?->content;

        return view('front-page', [
            'items' => $items,
            'bio' => $bio,
        ]);
    }
}
