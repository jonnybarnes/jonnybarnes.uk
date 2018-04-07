<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bookmark;

class BookmarksController extends Controller
{
    /**
     * Show the most recent bookmarks.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $bookmarks = Bookmark::latest()->with('tags')->withCount('tags')->paginate(10);

        return view('bookmarks.index', compact('bookmarks'));
    }

    /**
     * Show a single bookmark.
     *
     * @param  \App\Models\Bookmark  $bookmark
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function show(Bookmark $bookmark)
    {
        $bookmark->loadMissing('tags');

        return view('bookmarks.show', compact('bookmark'));
    }
}
