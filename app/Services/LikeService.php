<?php

declare(strict_types=1);

namespace App\Services;

use App\Like;
use App\Jobs\ProcessLike;

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
        if (array_get($request, 'properties.like-of.0')) {
            //micropub request
            $url = normalize_url(array_get($request, 'properties.like-of.0'));
        }
        if (array_get($request, 'like-of')) {
            $url = normalize_url(array_get($request, 'like-of'));
        }

        if (!isset($url)) {
            throw new \Exception();
        }

        $like = Like::create(['url' => $url]);
        ProcessLike::dispatch($like);

        return $like;
    }
}
