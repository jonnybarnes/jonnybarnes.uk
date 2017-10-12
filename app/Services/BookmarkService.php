<?php

declare(strict_types=1);

namespace App\Services;

use App\Bookmark;
use Illuminate\Http\Request;
use App\Jobs\ProcessBookmark;

class BookmarkService
{
    /**
     * Create a new Bookmark.
     *
     * @param  Request $request
     */
    public function createLike(Request $request): Bookmark
    {
        if ($request->header('Content-Type') == 'application/json') {
            //micropub request
            $url = normalize_url($request->input('properties.bookmark-of.0'));
            $name = $request->input('properties.name.0');
            $content = $request->input('properties.content.0');
            $categories = $request->input('properties.category');
        }
        if (
            ($request->header('Content-Type') == 'x-www-url-formencoded')
            ||
            ($request->header('Content-Type') == 'multipart/form-data')
        ) {
            $url = normalize_url($request->input('bookmark-of'));
            $name = $request->input('name');
            $content = $request->input('content');
            $categories = $request->input('category[]');
        }

        $bookmark = Bookmark::create([
            'url' => $url,
            'name' => $name,
            'content' => $content,
        ]);

        foreach($categories as $category) {
            $tag = Tag::firstOrCreate(['tag' => $category]);
            $bookmark->tags()->save($tag);
        }

        ProcessBookmark::dispatch($bookmark);

        return $Bookmark;
    }
}
