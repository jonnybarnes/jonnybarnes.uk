<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InternetArchiveException;
use App\Jobs\ProcessBookmark;
use App\Jobs\SyndicateBookmarkToTwitter;
use App\Models\Bookmark;
use App\Models\SyndicationTarget;
use App\Models\Tag;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class BookmarkService extends Service
{
    /**
     * Create a new Bookmark.
     *
     * @param array $request Data from request()->all()
     * @param string|null $client
     * @return Bookmark
     */
    public function create(array $request, ?string $client = null): Bookmark
    {
        if (Arr::get($request, 'properties.bookmark-of.0')) {
            //micropub request
            $url = normalize_url(Arr::get($request, 'properties.bookmark-of.0'));
            $name = Arr::get($request, 'properties.name.0');
            $content = Arr::get($request, 'properties.content.0');
            $categories = Arr::get($request, 'properties.category');
        }
        if (Arr::get($request, 'bookmark-of')) {
            $url = normalize_url(Arr::get($request, 'bookmark-of'));
            $name = Arr::get($request, 'name');
            $content = Arr::get($request, 'content');
            $categories = Arr::get($request, 'category');
        }

        $bookmark = Bookmark::create([
            'url' => $url,
            'name' => $name,
            'content' => $content,
        ]);

        foreach ((array) $categories as $category) {
            $tag = Tag::firstOrCreate(['tag' => $category]);
            $bookmark->tags()->save($tag);
        }

        $mpSyndicateTo = null;
        if (Arr::get($request, 'mp-syndicate-to')) {
            $mpSyndicateTo = Arr::get($request, 'mp-syndicate-to');
        }
        if (Arr::get($request, 'properties.mp-syndicate-to')) {
            $mpSyndicateTo = Arr::get($request, 'properties.mp-syndicate-to');
        }
        $mpSyndicateTo = Arr::wrap($mpSyndicateTo);
        foreach ($mpSyndicateTo as $uid) {
            $target = SyndicationTarget::where('uid', $uid)->first();
            if ($target && $target->service_name === 'Twitter') {
                SyndicateBookmarkToTwitter::dispatch($bookmark);

                break;
            }
        }

        ProcessBookmark::dispatch($bookmark);

        return $bookmark;
    }

    /**
     * Given a URL, attempt to save it to the Internet Archive.
     *
     * @param  string  $url
     * @return string
     *
     * @throws InternetArchiveException
     */
    public function getArchiveLink(string $url): string
    {
        $client = resolve(Client::class);
        try {
            $response = $client->request('GET', 'https://web.archive.org/save/' . $url);
        } catch (ClientException $e) {
            //throw an exception to be caught
            throw new InternetArchiveException();
        }
        if ($response->hasHeader('Content-Location')) {
            if (Str::startsWith(Arr::get($response->getHeader('Content-Location'), 0), '/web')) {
                return $response->getHeader('Content-Location')[0];
            }
        }

        //throw an exception to be caught
        throw new InternetArchiveException();
    }
}
