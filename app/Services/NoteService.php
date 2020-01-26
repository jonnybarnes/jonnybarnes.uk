<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\{SendWebMentions, SyndicateNoteToTwitter};
use App\Models\{Media, Note, Place};
use Illuminate\Support\{Arr, Str};

class NoteService
{
    /**
     * Create a new note.
     *
     * @param  array  $request Data from request()->all()
     * @param  string  $client
     * @return \App\Note
     */
    public function createNote(array $request, ?string $client = null): Note
    {
        $note = Note::create(
            [
                'note' => $this->getContent($request),
                'in_reply_to' => $this->getInReplyTo($request),
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

        //syndication targets
        if (count($this->getSyndicationTargets($request)) > 0) {
            if (in_array('twitter', $this->getSyndicationTargets($request))) {
                dispatch(new SyndicateNoteToTwitter($note));
            }
        }

        return $note;
    }

    /**
     * Get the content from the request to create a new note.
     *
     * @param  array  $request Data from request()->all()
     * @return string|null
     */
    private function getContent(array $request): ?string
    {
        if (Arr::get($request, 'properties.content.0.html')) {
            return Arr::get($request, 'properties.content.0.html');
        }
        if (is_string(Arr::get($request, 'properties.content.0'))) {
            return Arr::get($request, 'properties.content.0');
        }

        return Arr::get($request, 'content');
    }

    /**
     * Get the in-reply-to from the request to create a new note.
     *
     * @param  array  $request Data from request()->all()
     * @return string|null
     */
    private function getInReplyTo(array $request): ?string
    {
        if (Arr::get($request, 'properties.in-reply-to.0')) {
            return Arr::get($request, 'properties.in-reply-to.0');
        }

        return Arr::get($request, 'in-reply-to');
    }

    /**
     * Get the published time from the request to create a new note.
     *
     * @param  array  $request Data from request()->all()
     * @return string|null
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
     *
     * @param  array  $request Data from request()->all()
     * @return string|null
     */
    private function getLocation(array $request): ?string
    {
        $location = Arr::get($request, 'properties.location.0') ?? Arr::get($request, 'location');
        if (is_string($location) && substr($location, 0, 4) == 'geo:') {
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
     *
     * @param  array  $request Data from request()->all()
     * @return \App\Models\Place|null
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
     *
     * @param  array  $request Data from request()->all()
     * @return string|null
     */
    private function getSwarmUrl(array $request): ?string
    {
        if (stristr(Arr::get($request, 'properties.syndication.0', ''), 'swarmapp')) {
            return Arr::get($request, 'properties.syndication.0');
        }

        return null;
    }

    /**
     * Get the syndication targets from the request to create a new note.
     *
     * @param  array  $request Data from request()->all()
     * @return array
     */
    private function getSyndicationTargets(array $request): array
    {
        $syndication = [];
        $targets = Arr::pluck(config('syndication.targets'), 'uid', 'service.name');
        $mpSyndicateTo = Arr::get($request, 'mp-syndicate-to') ?? Arr::get($request, 'properties.mp-syndicate-to');
        if (is_string($mpSyndicateTo)) {
            $service = array_search($mpSyndicateTo, $targets);
            if ($service == 'Twitter') {
                $syndication[] = 'twitter';
            }
        }
        if (is_array($mpSyndicateTo)) {
            foreach ($mpSyndicateTo as $uid) {
                $service = array_search($uid, $targets);
                if ($service == 'Twitter') {
                    $syndication[] = 'twitter';
                }
            }
        }

        return $syndication;
    }

    /**
     * Get the media URLs from the request to create a new note.
     *
     * @param  array  $request Data from request()->all()
     * @return array
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
     *
     * @param  array  $request Data from request()->all()
     * @return string|null
     */
    private function getInstagramUrl(array $request): ?string
    {
        if (Str::startsWith(Arr::get($request, 'properties.syndication.0'), 'https://www.instagram.com')) {
            return Arr::get($request, 'properties.syndication.0');
        }

        return null;
    }
}
