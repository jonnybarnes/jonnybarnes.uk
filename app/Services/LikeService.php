<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessLike;
use App\Models\Like;
use Illuminate\Support\Arr;

class LikeService
{
    /**
     * Create a new Like.
     *
     * @param  array $request
     * @return Like $like
     */
    public function createLike(array $request): Like
    {
        if (Arr::get($request, 'properties.like-of.0')) {
            //micropub request
            $url = normalize_url(Arr::get($request, 'properties.like-of.0'));
        }
        if (Arr::get($request, 'like-of')) {
            $url = normalize_url(Arr::get($request, 'like-of'));
        }

        $like = Like::create(['url' => $url]);
        ProcessLike::dispatch($like);

        return $like;
    }
}
