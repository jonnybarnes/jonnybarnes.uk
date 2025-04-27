<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InternetArchiveException;
use App\Jobs\ProcessBookmark;
use App\Models\Bookmark;
use App\Models\Tag;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BookmarkService
{
    /**
     * Create a new Bookmark.
     */
    public function create(array $data): Bookmark
    {
        if (Arr::get($data, 'properties.bookmark-of.0')) {
            // micropub request
            $url = normalize_url(Arr::get($data, 'properties.bookmark-of.0'));
            $name = Arr::get($data, 'properties.name.0');
            $content = Arr::get($data, 'properties.content.0');
            $categories = Arr::get($data, 'properties.category');
        }
        if (Arr::get($data, 'bookmark-of')) {
            $url = normalize_url(Arr::get($data, 'bookmark-of'));
            $name = Arr::get($data, 'name');
            $content = Arr::get($data, 'content');
            $categories = Arr::get($data, 'category');
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

        ProcessBookmark::dispatch($bookmark);

        return $bookmark;
    }

    /**
     * Given a URL, attempt to save it to the Internet Archive.
     *
     * @throws InternetArchiveException
     * @throws GuzzleException
     */
    public function getArchiveLink(string $url): string
    {
        $client = resolve(Client::class);
        try {
            $response = $client->request('GET', 'https://web.archive.org/save/' . $url);
        } catch (ClientException $e) {
            // throw an exception to be caught
            throw new InternetArchiveException;
        }
        if ($response->hasHeader('Content-Location')) {
            if (Str::startsWith(Arr::get($response->getHeader('Content-Location'), 0), '/web')) {
                return $response->getHeader('Content-Location')[0];
            }
        }

        // throw an exception to be caught
        throw new InternetArchiveException;
    }
}
