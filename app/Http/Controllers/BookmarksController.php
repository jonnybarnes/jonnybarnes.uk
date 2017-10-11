<?php

namespace App\Http\Controllers;

use App\Bookmark;

class BookmarksController extends Controller
{
    public function index()
    {
        $bookmarks = Bookmark::latest()->with('tags')->withCount('tags')->paginate(10);

        return view('bookmarks.index', compact('bookmarks'));
    }

    public function show(Bookmark $bookmark)
    {
        $bookmark->loadMissing('tags');

        return view('bookmarks.show', compact('bookmark'));
    }
}
