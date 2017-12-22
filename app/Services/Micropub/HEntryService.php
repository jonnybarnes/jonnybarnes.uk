<?php

namespace App\Services\Micropub;

use App\Services\{BookmarkService, LikeService, NoteService};

class HEntryService
{
    public function process(array $request, string $client = null)
    {
        if (array_get($request, 'properties.like-of') || array_get($request, 'like-of')) {
            $like = resolve(LikeService::class)->createLike($request);

            return $like->longurl;
        }

        if (array_get($request, 'properties.bookmark-of') || array_get($request, 'bookmark-of')) {
            $bookmark = resolve(BookmarkService::class)->createBookmark($request);

            return $bookmark->longurl;
        }

        $note = resolve(NoteService::class)->createNote($request, $client);

        return $note->longurl;
    }
}
