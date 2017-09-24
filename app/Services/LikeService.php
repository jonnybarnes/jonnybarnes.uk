<?php

declare(strict_types=1);

namespace App\Services;

use App\Like;
use App\Jobs\ProcessLike;
use Illuminate\Http\Request;

class LikeService
{
    /**
     * Create a new Like.
     *
     * @param  Request $request
     */
    public function createLike(Request $request): Like
    {
        if ($request->header('Content-Type') == 'application/json') {
            //micropub request
            $url = normalize_url($request->input('properties.like-of.0'));
        }
        if (
            ($request->header('Content-Type') == 'x-www-url-formencoded')
            ||
            ($request->header('Content-Type') == 'multipart/form-data')
        ) {
            $url = normalize_url($request->input('like-of'));
        }

        $like = Like::create(['url' => $url]);
        ProcessLike::dispatch($like);

        return $like;
    }
}
