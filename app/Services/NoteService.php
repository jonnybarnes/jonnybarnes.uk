<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendWebMentions;
use App\Jobs\SyndicateNoteToBluesky;
use App\Jobs\SyndicateNoteToMastodon;
use App\Models\Media;
use App\Models\Note;
use App\Models\Place;
use App\Models\SyndicationTarget;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class NoteService extends Service
{
    /**
     * Create a new note.
     */
    public function create(array $request, ?string $client = null): Note
    {
        $note = Note::create(
            [
                'note' => $this->getDataByKey($request, 'content'),
                'in_reply_to' => $this->getDataByKey($request, 'in-reply-to'),
                'client_id' => $client,
            ]
        );

        if ($this->getPublished($request)) {
            $note->created_at = $note->updated_at = $this->getPublished($request);
        }

        $note->location = $this->getLocation($request);

        if ($this->getCheckin($request)) {
            $note->place()->associate($this->getCheckin($request));
            $note->swarm_url = $this->getSwarmUrl($request);
        }

        $note->instagram_url = $this->getInstagramUrl($request);

        foreach ($this->getMedia($request) as $media) {
            $note->media()->save($media);
        }

        $note->save();

        dispatch(new SendWebMentions($note));

        if (in_array('mastodon', $this->getSyndicationTargets($request), true)) {
            dispatch(new SyndicateNoteToMastodon($note));
        }

        if (in_array('bluesky', $this->getSyndicationTargets($request), true)) {
            dispatch(new SyndicateNoteToBluesky($note));
        }

        return $note;
    }

    /**
     * Get the published time from the request to create a new note.
     */
    private function getPublished(array $request): ?string
    {
        if (Arr::get($request, 'properties.published.0')) {
            return carbon(Arr::get($request, 'properties.published.0'))
                ->toDateTimeString();
        }
        if (Arr::get($request, 'published')) {
            return carbon(Arr::get($request, 'published'))->toDateTimeString();
        }

        return null;
    }

    /**
     * Get the location data from the request to create a new note.
     */
    private function getLocation(array $request): ?string
    {
        $location = Arr::get($request, 'properties.location.0') ?? Arr::get($request, 'location');
        if (is_string($location) && str_starts_with($location, 'geo:')) {
            preg_match_all(
                '/([0-9\.\-]+)/',
                $location,
                $matches
            );

            return $matches[0][0] . ', ' . $matches[0][1];
        }

        return null;
    }

    /**
     * Get the checkin data from the request to create a new note. This will be a Place.
     */
    private function getCheckin(array $request): ?Place
    {
        $location = Arr::get($request, 'location');
        if (is_string($location) && Str::startsWith($location, config('app.url'))) {
            return Place::where(
                'slug',
                basename(
                    parse_url(
                        $location,
                        PHP_URL_PATH
                    )
                )
            )->first();
        }
        if (Arr::get($request, 'checkin')) {
            try {
                $place = resolve(PlaceService::class)->createPlaceFromCheckin(
                    Arr::get($request, 'checkin')
                );
            } catch (\InvalidArgumentException $e) {
                return null;
            }

            return $place;
        }
        if (Arr::get($location, 'type.0') === 'h-card') {
            try {
                $place = resolve(PlaceService::class)->createPlaceFromCheckin(
                    $location
                );
            } catch (\InvalidArgumentException $e) {
                return null;
            }

            return $place;
        }

        return null;
    }

    /**
     * Get the Swarm URL from the syndication data in the request to create a new note.
     */
    private function getSwarmUrl(array $request): ?string
    {
        if (str_contains(Arr::get($request, 'properties.syndication.0', ''), 'swarmapp')) {
            return Arr::get($request, 'properties.syndication.0');
        }

        return null;
    }

    /**
     * Get the syndication targets from the request to create a new note.
     */
    private function getSyndicationTargets(array $request): array
    {
        $syndication = [];
        $mpSyndicateTo = Arr::get($request, 'mp-syndicate-to') ?? Arr::get($request, 'properties.mp-syndicate-to');
        $mpSyndicateTo = Arr::wrap($mpSyndicateTo);
        foreach ($mpSyndicateTo as $uid) {
            $target = SyndicationTarget::where('uid', $uid)->first();
            if ($target && $target->service_name === 'Mastodon') {
                $syndication[] = 'mastodon';
            }
            if ($target && $target->service_name === 'Bluesky') {
                $syndication[] = 'bluesky';
            }
        }

        return $syndication;
    }

    /**
     * Get the media URLs from the request to create a new note.
     */
    private function getMedia(array $request): array
    {
        $media = [];
        $photos = Arr::get($request, 'photo') ?? Arr::get($request, 'properties.photo');

        if (isset($photos)) {
            foreach ((array) $photos as $photo) {
                // check the media was uploaded to my endpoint, and use path
                if (Str::startsWith($photo, config('filesystems.disks.s3.url'))) {
                    $path = substr($photo, strlen(config('filesystems.disks.s3.url')));
                    $media[] = Media::where('path', ltrim($path, '/'))->firstOrFail();
                } else {
                    $newMedia = Media::firstOrNew(['path' => $photo]);
                    // currently assuming this is a photo from Swarm or OwnYourGram
                    $newMedia->type = 'image';
                    $newMedia->save();
                    $media[] = $newMedia;
                }
            }
        }

        return $media;
    }

    /**
     * Get the Instagram photo URL from the request to create a new note.
     */
    private function getInstagramUrl(array $request): ?string
    {
        if (Str::startsWith(Arr::get($request, 'properties.syndication.0'), 'https://www.instagram.com')) {
            return Arr::get($request, 'properties.syndication.0');
        }

        return null;
    }
}
