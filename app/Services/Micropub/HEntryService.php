<?php

namespace App\Services\Micropub;

use Illuminate\Http\Request;
use App\Services\{BookmarkService, LikeService, NoteService};

class HEntryService
{
    public function process(Request $request)
    {
        if ($request->has('properties.like-of') || $request->has('like-of')) {
            $like = resolve(LikeService::class)->createLike($request);

            return $like->longurl;
        }

        if ($request->has('properties.bookmark-of') || $request->has('bookmark-of')) {
            $bookmark = resolve(BookmarkService::class)->createBookmark($request);

            return $bookmark->longurl;
        }

        $note = resolve(NoteService::class)->createNote($request);

        return $note->longurl;
    }
}
