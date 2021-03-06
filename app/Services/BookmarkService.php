<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InternetArchiveException;
use App\Jobs\ProcessBookmark;
use App\Jobs\SyndicateBookmarkToTwitter;
use App\Models\{Bookmark, Tag};
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\{Arr, Str};
use Ramsey\Uuid\Uuid;
use Spatie\Browsershot\Browsershot;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;

class BookmarkService
{
    /**
     * Create a new Bookmark.
     *
     * @param array $request Data from request()->all()
     * @return Bookmark
     */
    public function createBookmark(array $request): Bookmark
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

        $targets = Arr::pluck(config('syndication.targets'), 'uid', 'service.name');
        $mpSyndicateTo = null;
        if (Arr::get($request, 'mp-syndicate-to')) {
            $mpSyndicateTo = Arr::get($request, 'mp-syndicate-to');
        }
        if (Arr::get($request, 'properties.mp-syndicate-to')) {
            $mpSyndicateTo = Arr::get($request, 'properties.mp-syndicate-to');
        }
        if (is_string($mpSyndicateTo)) {
            $service = array_search($mpSyndicateTo, $targets);
            if ($service == 'Twitter') {
                SyndicateBookmarkToTwitter::dispatch($bookmark);
            }
        }
        if (is_array($mpSyndicateTo)) {
            foreach ($mpSyndicateTo as $uid) {
                $service = array_search($uid, $targets);
                if ($service == 'Twitter') {
                    SyndicateBookmarkToTwitter::dispatch($bookmark);
                }
            }
        }

        ProcessBookmark::dispatch($bookmark);

        return $bookmark;
    }

    /**
     * Given a URL, use browsershot to save an image of the page.
     *
     * @param string $url
     * @return string  The uuid for the screenshot
     * @throws CouldNotTakeBrowsershot
     */
    public function saveScreenshot(string $url): string
    {
        $browsershot = new Browsershot();

        $uuid = Uuid::uuid4();

        $browsershot->url($url)
                    ->setIncludePath('$PATH:/usr/local/bin')
                    ->noSandbox()
                    ->windowSize(960, 640)
                    ->save(public_path() . '/assets/img/bookmarks/' . $uuid . '.png');

        return $uuid->toString();
    }

    /**
     * Given a URL, attempt to save it to the Internet Archive.
     *
     * @param string $url
     * @return string
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
