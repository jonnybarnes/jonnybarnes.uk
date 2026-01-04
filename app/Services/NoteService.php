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

class NoteService
{
    /**
     * Create a new note.
     */
    public function create(array $data): Note
    {
        // Get the content we want to save
        if (is_string($data['content'])) {
            $content = $data['content'];
        } elseif (isset($data['content']['html'])) {
            $content = $data['content']['html'];
        } else {
            $content = null;
        }

        $note = Note::create(
            [
                'note' => $content,
                'in_reply_to' => $data['in-reply-to'],
                'client_id' => $data['token_data']['client_id'],
            ]
        );

        if ($published = $this->getPublished($data)) {
            $note->created_at = $note->updated_at = $published;
        }

        $note->location = $this->getLocation($data);

        if ($this->getCheckin($data)) {
            $note->place()->associate($this->getCheckin($data));
            $note->swarm_url = $this->getSwarmUrl($data);
        }

        // $note->instagram_url = $this->getInstagramUrl($request);

        foreach ($this->getMedia($data) as $index => $media) {
            $note->media()->attach($media['value']->id, [
                'alt_text' => $media['alt'],
                'order' => $index,
            ]);
        }

        $note->save();

        dispatch(new SendWebMentions($note));

        $this->dispatchSyndicationJobs($note, $data);

        return $note;
    }

    /**
     * Get the published time from the request to create a new note.
     */
    private function getPublished(array $data): ?string
    {
        if ($data['published']) {
            return carbon($data['published'])->toDateTimeString();
        }

        return null;
    }

    /**
     * Get the location data from the request to create a new note.
     */
    private function getLocation(array $data): ?string
    {
        $location = Arr::get($data, 'location');

        if (is_string($location) && str_starts_with($location, 'geo:')) {
            preg_match_all(
                '/([0-9.\-]+)/',
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
    private function getCheckin(array $data): ?Place
    {
        $location = Arr::get($data, 'location');
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
        if (Arr::get($data, 'checkin')) {
            try {
                $place = resolve(PlaceService::class)->createPlaceFromCheckin(
                    Arr::get($data, 'checkin')
                );
            } catch (\InvalidArgumentException) {
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
    private function getSwarmUrl(array $data): ?string
    {
        $syndication = Arr::get($data, 'syndication');
        if ($syndication === null) {
            return null;
        }

        if (str_contains($syndication, 'swarmapp')) {
            return $syndication;
        }

        return null;
    }

    /**
     * Dispatch syndication jobs based on the request data.
     */
    private function dispatchSyndicationJobs(Note $note, array $request): void
    {
        // If no syndication targets are specified, return early
        if (empty($request['mp-syndicate-to'])) {
            return;
        }

        // Get the configured syndication targets
        $syndicationTargets = SyndicationTarget::all();

        foreach ($syndicationTargets as $target) {
            // Check if the target is in the request data
            if (in_array($target->uid, $request['mp-syndicate-to'], true)) {
                // Dispatch the appropriate job based on the target service name
                switch ($target->service_name) {
                    case 'Mastodon':
                        dispatch(new SyndicateNoteToMastodon($note));
                        break;
                    case 'Bluesky':
                        dispatch(new SyndicateNoteToBluesky($note));
                        break;
                }
            }
        }
    }

    /**
     * Get the media URLs from the request to create a new note.
     */
    private function getMedia(array $data): array
    {
        $media = [];
        $photos = Arr::get($data, 'photos');

        if (isset($photos)) {
            foreach ((array) $photos as $photo) {
                // $photo can be a string of the URL opf the photo
                // or it can be an object with a `value` and `alt`
                $photoUrl = null;
                $photoAlt = null;
                if (is_string($photo)) {
                    $photoUrl = $photo;
                } elseif (is_array($photo)) {
                    $photoUrl = $photo['value'];
                    $photoAlt = $photo['alt'];
                }

                if (empty($photoUrl)) {
                    continue;
                }

                // check the media was uploaded to my endpoint, and use path
                if (Str::startsWith($photoUrl, config('filesystems.disks.public.url'))) {
                    $path = substr($photoUrl, strlen(config('filesystems.disks.public.url')));
                    $media[] = [
                        'value' => Media::where('path', ltrim($path, '/'))->firstOrFail(),
                        'alt' => $photoAlt,
                    ];
                } else {
                    $newMedia = Media::firstOrNew(['path' => $photoUrl]);
                    // currently assuming this is a photo from Swarm or OwnYourGram
                    $newMedia->type = 'image';
                    $newMedia->save();
                    $media[] = [
                        'value' => $newMedia,
                        'alt' => $photoAlt,
                    ];
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
