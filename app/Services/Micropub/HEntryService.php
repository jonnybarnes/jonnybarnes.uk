<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use App\Services\ArticleService;
use App\Services\BookmarkService;
use App\Services\LikeService;
use App\Services\NoteService;
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
            return resolve(LikeService::class)->create($request)->longurl;
        }

        if (Arr::get($request, 'properties.bookmark-of') || Arr::get($request, 'bookmark-of')) {
            return resolve(BookmarkService::class)->create($request)->longurl;
        }

        if (Arr::get($request, 'properties.name') || Arr::get($request, 'name')) {
            return resolve(ArticleService::class)->create($request)->longurl;
        }

        return resolve(NoteService::class)->create($request, $client)->longurl;
    }
}
