<?php

declare(strict_types=1);

namespace App\Services;

use App\Tag;
use App\Bookmark;
use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Jobs\ProcessBookmark;
use Spatie\Browsershot\Browsershot;
use App\Jobs\SyndicateBookmarkToTwitter;
use App\Jobs\SyndicateBookmarkToFacebook;
use GuzzleHttp\Exception\ClientException;
use App\Exceptions\InternetArchiveException;

class BookmarkService
{
    /**
     * Create a new Bookmark.
     *
     * @param  Request $request
     */
    public function createBookmark(Request $request): Bookmark
    {
        if ($request->header('Content-Type') == 'application/json') {
            //micropub request
            $url = normalize_url($request->input('properties.bookmark-of.0'));
            $name = $request->input('properties.name.0');
            $content = $request->input('properties.content.0');
            $categories = $request->input('properties.category');
        }
        if (($request->header('Content-Type') == 'application/x-www-form-urlencoded')
            ||
            (str_contains($request->header('Content-Type'), 'multipart/form-data'))
        ) {
            $url = normalize_url($request->input('bookmark-of'));
            $name = $request->input('name');
            $content = $request->input('content');
            $categories = $request->input('category');
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

        $targets = array_pluck(config('syndication.targets'), 'uid', 'service.name');
        $mpSyndicateTo = null;
        if ($request->has('mp-syndicate-to')) {
            $mpSyndicateTo = $request->input('mp-syndicate-to');
        }
        if ($request->has('properties.mp-syndicate-to')) {
            $mpSyndicateTo = $request->input('properties.mp-syndicate-to');
        }
        if (is_string($mpSyndicateTo)) {
            $service = array_search($mpSyndicateTo, $targets);
            if ($service == 'Twitter') {
                SyndicateBookmarkToTwitter::dispatch($bookmark);
            }
            if ($service == 'Facebook') {
                SyndicateBookmarkToFacebook::dispatch($bookmark);
            }
        }
        if (is_array($mpSyndicateTo)) {
            foreach ($mpSyndicateTo as $uid) {
                $service = array_search($uid, $targets);
                if ($service == 'Twitter') {
                    SyndicateBookmarkToTwitter::dispatch($bookmark);
                }
                if ($service == 'Facebook') {
                    SyndicateBookmarkToFacebook::dispatch($bookmark);
                }
            }
        }

        ProcessBookmark::dispatch($bookmark);

        return $bookmark;
    }

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

    public function getArchiveLink(string $url): string
    {
        $client = resolve(Client::class);
        try {
            $response = $client->request('GET', 'https://web.archive.org/save/' . $url);
        } catch (ClientException $e) {
            //throw an exception to be caught
            throw new InternetArchiveException;
        }
        if ($response->hasHeader('Content-Location')) {
            if (starts_with(array_get($response->getHeader('Content-Location'), 0), '/web')) {
                return $response->getHeader('Content-Location')[0];
            }
        }

        //throw an exception to be caught
        throw new InternetArchiveException;
    }
}
