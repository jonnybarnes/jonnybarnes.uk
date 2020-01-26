<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use App\Services\{BookmarkService, LikeService, NoteService};
use Illuminate\Support\Arr;

class HEntryService
{
    /**
     * Create the relavent model from some h-entry data.
     *
     * @param  array  $request Data from request()->all()
     * @param  string|null  $client The micropub client that made the request
     * @return string|null
     */
    public function process(array $request, ?string $client = null): ?string
    {
        if (Arr::get($request, 'properties.like-of') || Arr::get($request, 'like-of')) {
            $like = resolve(LikeService::class)->createLike($request);

            return $like->longurl;
        }

        if (Arr::get($request, 'properties.bookmark-of') || Arr::get($request, 'bookmark-of')) {
            $bookmark = resolve(BookmarkService::class)->createBookmark($request);

            return $bookmark->longurl;
        }

        $note = resolve(NoteService::class)->createNote($request, $client);

        return $note->longurl;
    }
}
