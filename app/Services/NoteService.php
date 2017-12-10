<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use App\{Media, Note, Place};
use App\Jobs\{SendWebMentions, SyndicateNoteToFacebook, SyndicateNoteToTwitter};

class NoteService
{
    /**
     * Create a new note.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \App\Note $note
     */
    public function createNote(Request $request): Note
    {
        $note = Note::create(
            [
                'note' => $this->getContent($request),
                'in_reply_to' => $this->getInReplyTo($request),
                'client_id' => $this->getClientId($request),
            ]
        );

        if ($this->getPublished($request)) {
            $note->created_at = $note->updated_at = $this->getPublished($request);
        }

        $note->location = $this->getLocation($request);

        if ($this->getCheckin($request)) {
            $note->place()->associate($this->getCheckin($request));
            $note->swarm_url = $this->getSwarmUrl($request);
            if ($note->note === null || $note->note == '') {
                $note->note = 'I’ve just checked in with Swarm';
            }
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

    private function getClientId(Request $request): string
    {
        return resolve(TokenService::class)
            ->validateToken($request->bearerToken())
            ->getClaim('client_id');
    }

    private function getContent(Request $request): ?string
    {
        if (array_get($request, 'properties.content.0.html')) {
            return array_get($request, 'properties.content.0.html');
        }
        if (is_string(array_get($request, 'properties.content.0'))) {
            return array_get($request, 'properties.content.0');
        }

        return $request->input('content');
    }

    private function getInReplyTo(Request $request): ?string
    {
        if (array_get($request, 'properties.in-reply-to.0')) {
            return array_get($request, 'properties.in-reply-to.0');
        }

        return $request->input('in-reply-to');
    }

    private function getPublished(Request $request): ?string
    {
        if (array_get($request, 'properties.published.0')) {
            return carbon(array_get($request, 'properties.published.0'))
                        ->toDateTimeString();
        }
        if ($request->input('published')) {
            return carbon($request->input('published'))->toDateTimeString();
        }

        return null;
    }

    private function getLocation(Request $request): ?string
    {
        if (is_string(array_get($request, 'properties.location.0'))) {
            $location = array_get($request, 'properties.location.0');
        }
        if ($request->input('location')) {
            $location = $request->input('location');
        }
        if (isset($location) && substr($location, 0, 4) == 'geo:') {
            preg_match_all(
                '/([0-9\.\-]+)/',
                $location,
                $matches
            );

            return $matches[0][0] . ', ' . $matches[0][1];
        }

        return null;
    }

    private function getCheckin($request): ?Place
    {
        if (array_get($request, 'properties.location.0.type.0') === 'h-card') {
            try {
                $place = resolve(PlaceService::class)->createPlaceFromCheckin(
                    $request->input('properties.location.0')
                );
            } catch (\InvalidArgumentException $e) {
                return null;
            }

            return $place;
        }
        if (starts_with(array_get($request, 'properties.location.0'), config('app.url'))) {
            return Place::where(
                'slug',
                basename(
                    parse_url(
                        array_get($request, 'properties.location.0'),
                        PHP_URL_PATH
                    )
                )
            )->first();
        }
        if (array_get($request, 'properties.checkin')) {
            try {
                $place = resolve(PlaceService::class)->createPlaceFromCheckin(
                    $request->input('properties.checkin.0')
                );
            } catch (\InvalidArgumentException $e) {
                return null;
            }

            return $place;
        }

        return null;
    }

    private function getSwarmUrl(Request $request): ?string
    {
        if (stristr(array_get($request, 'properties.syndication.0', ''), 'swarmapp')) {
            return array_get($request, 'properties.syndication.0');
        }

        return null;
    }

    private function getSyndicationTargets(Request $request): array
    {
        $syndication = [];
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

    private function getMedia(Request $request): array
    {
        $media = [];
        $photos = $request->input('photo') ?? $request->input('properties.photo');

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

    private function getInstagramUrl(Request $request): ?string
    {
        if (starts_with(array_get($request, 'properties.syndication.0'), 'https://www.instagram.com')) {
            return array_get($request, 'properties.syndication.0');
        }

        return null;
    }
}
