<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{Media, Note, Place};
use App\Jobs\{SendWebMentions, SyndicateNoteToFacebook, SyndicateNoteToTwitter};

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
            if (in_array('facebook', $this->getSyndicationTargets($request))) {
                dispatch(new SyndicateNoteToFacebook($note));
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
        if (array_get($request, 'properties.content.0.html')) {
            return array_get($request, 'properties.content.0.html');
        }
        if (is_string(array_get($request, 'properties.content.0'))) {
            return array_get($request, 'properties.content.0');
        }

        return array_get($request, 'content');
    }

    /**
     * Get the in-reply-to from the request to create a new note.
     *
     * @param  array  $request Data from request()->all()
     * @return string|null
     */
    private function getInReplyTo(array $request): ?string
    {
        if (array_get($request, 'properties.in-reply-to.0')) {
            return array_get($request, 'properties.in-reply-to.0');
        }

        return array_get($request, 'in-reply-to');
    }

    /**
     * Get the published time from the request to create a new note.
     *
     * @param  array  $request Data from request()->all()
     * @return string|null
     */
    private function getPublished(array $request): ?string
    {
        if (array_get($request, 'properties.published.0')) {
            return carbon(array_get($request, 'properties.published.0'))
                        ->toDateTimeString();
        }
        if (array_get($request, 'published')) {
            return carbon(array_get($request, 'published'))->toDateTimeString();
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
        $location = array_get($request, 'properties.location.0') ?? array_get($request, 'location');
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
        $location = array_get($request, 'properties.location.0');
        if (array_get($location, 'type.0') === 'h-card') {
            try {
                $place = resolve(PlaceService::class)->createPlaceFromCheckin(
                    $location
                );
            } catch (\InvalidArgumentException $e) {
                return null;
            }

            return $place;
        }
        if (is_string($location) && starts_with($location, config('app.url'))) {
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
        if (array_get($request, 'properties.checkin')) {
            try {
                $place = resolve(PlaceService::class)->createPlaceFromCheckin(
                    array_get($request, 'properties.checkin.0')
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
        if (stristr(array_get($request, 'properties.syndication.0', ''), 'swarmapp')) {
            return array_get($request, 'properties.syndication.0');
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
        $targets = array_pluck(config('syndication.targets'), 'uid', 'service.name');
        $mpSyndicateTo = array_get($request, 'mp-syndicate-to') ?? array_get($request, 'properties.mp-syndicate-to');
        if (is_string($mpSyndicateTo)) {
            $service = array_search($mpSyndicateTo, $targets);
            if ($service == 'Twitter') {
                $syndication[] = 'twitter';
            }
            if ($service == 'Facebook') {
                $syndication[] = 'facebook';
            }
        }
        if (is_array($mpSyndicateTo)) {
            foreach ($mpSyndicateTo as $uid) {
                $service = array_search($uid, $targets);
                if ($service == 'Twitter') {
                    $syndication[] = 'twitter';
                }
                if ($service == 'Facebook') {
                    $syndication[] = 'facebook';
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
        $photos = array_get($request, 'photo') ?? array_get($request, 'properties.photo');

        if (isset($photos)) {
            foreach ((array) $photos as $photo) {
                // check the media was uploaded to my endpoint, and use path
                if (starts_with($photo, config('filesystems.disks.s3.url'))) {
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
        if (starts_with(array_get($request, 'properties.syndication.0'), 'https://www.instagram.com')) {
            return array_get($request, 'properties.syndication.0');
        }

        return null;
    }
}
