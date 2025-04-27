<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use App\Exceptions\InvalidTokenScopeException;
use App\Services\ArticleService;
use App\Services\BookmarkService;
use App\Services\LikeService;
use App\Services\NoteService;

class EntryHandler implements MicropubHandlerInterface
{
    /**
     * @throws InvalidTokenScopeException
     */
    public function handle(array $data)
    {
        $scopes = $data['token_data']['scope'];
        if (is_string($scopes)) {
            $scopes = explode(' ', $scopes);
        }

        if (! in_array('create', $scopes, true)) {
            throw new InvalidTokenScopeException;
        }

        $location = match (true) {
            isset($data['like-of']) => resolve(LikeService::class)->create($data)->url,
            isset($data['bookmark-of']) => resolve(BookmarkService::class)->create($data)->uri,
            isset($data['name']) => resolve(ArticleService::class)->create($data)->link,
            default => resolve(NoteService::class)->create($data)->uri,
        };

        return [
            'response' => 'created',
            'url' => $location,
        ];
    }
}
