<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bookmark;
use Illuminate\View\View;

class BookmarksController extends Controller
{
    /**
     * Show the most recent bookmarks.
     *
     * @return View
     */
    public function index(): View
    {
        $bookmarks = Bookmark::latest()->with('tags')->withCount('tags')->paginate(10);

        return view('bookmarks.index', compact('bookmarks'));
    }

    /**
     * Show a single bookmark.
     *
     * @param  Bookmark  $bookmark
     * @return View
     */
    public function show(Bookmark $bookmark): View
    {
        $bookmark->loadMissing('tags');

        return view('bookmarks.show', compact('bookmark'));
    }

    /**
     * Show bookmarks tagged with a specific tag.
     *
     * @param  string  $tag
     * @return View
     */
    public function tagged(string $tag): View
    {
        $bookmarks = Bookmark::whereHas('tags', function ($query) use ($tag) {
            $query->where('tag', $tag);
        })->latest()->with('tags')->withCount('tags')->paginate(10);

        return view('bookmarks.tagged', compact('bookmarks', 'tag'));
    }
}
