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
     */
    public function create(array $data): Like
    {
        if (Arr::get($data, 'properties.like-of.0')) {
            // micropub request
            $url = normalize_url(Arr::get($data, 'properties.like-of.0'));
        }
        if (Arr::get($data, 'like-of')) {
            $url = normalize_url(Arr::get($data, 'like-of'));
        }

        $like = Like::create(['url' => $url]);
        ProcessLike::dispatch($like);

        return $like;
    }
}
