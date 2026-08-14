<?php

declare(strict_types=1);

namespace App\Services\Micropub\Handlers;

use App\Exceptions\InvalidTokenScopeException;
use App\Services\ArticleService;
use App\Services\BookmarkService;
use App\Services\LikeService;
use App\Services\Micropub\Data\EntryData;
use App\Services\Micropub\Data\MicropubData;
use App\Services\NoteService;

class EntryHandler implements MicropubHandlerInterface
{
    public function dataClass(): string
    {
        return EntryData::class;
    }

    /**
     * @throws InvalidTokenScopeException
     */
    public function handle(MicropubData $data): array
    {
        assert($data instanceof EntryData);

        $scopes = $data->tokenData['scope'];
        $scopes = explode(' ', $scopes);

        if (! in_array('create', $scopes, true)) {
            throw new InvalidTokenScopeException;
        }

        $dataArray = $data->toArray();
        $location = match (true) {
            isset($dataArray['like-of']) => resolve(LikeService::class)->create($dataArray)->url,
            isset($dataArray['bookmark-of']) => resolve(BookmarkService::class)->create($dataArray)->uri,
            isset($dataArray['name']) => resolve(ArticleService::class)->create($dataArray)->link,
            default => resolve(NoteService::class)->create($dataArray)->uri,
        };

        return [
            'response' => 'created',
            'url' => $location,
        ];
    }
}
