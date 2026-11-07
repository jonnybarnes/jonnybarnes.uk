<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use App\Models\Note;

/**
 * Builds the microformats2 JSON for a q=source query.
 */
class NoteSource
{
    /**
     * Per the spec, the type is only included when all properties are returned.
     *
     * @param  string[]  $requested  The properties to return, or empty for all of them
     */
    public function toArray(Note $note, array $requested = []): array
    {
        $properties = $this->properties($note);

        if ($requested !== []) {
            return ['properties' => (object) array_intersect_key($properties, array_flip($requested))];
        }

        return ['type' => ['h-entry'], 'properties' => (object) $properties];
    }

    private function properties(Note $note): array
    {
        $media = $note->media;

        return array_filter([
            // the raw source, as the note accessor returns the rendered HTML
            'content' => array_filter([$note->getRawOriginal('note')], fn ($value) => $value !== null),
            'published' => [$note->created_at->toIso8601String()],
            'in-reply-to' => array_filter([$note->in_reply_to]),
            'category' => $note->tags->pluck('tag')->all(),
            'photo' => $media->where('type', 'image')->map(fn ($item) => $item->pivot->alt_text
                ? ['value' => $item->url, 'alt' => $item->pivot->alt_text]
                : $item->url)->values()->all(),
            'video' => $media->where('type', 'video')->pluck('url')->all(),
            'audio' => $media->where('type', 'audio')->pluck('url')->all(),
            'syndication' => array_values(array_filter([
                $note->tweet_id ? 'https://twitter.com/i/web/status/'.$note->tweet_id : null,
                $note->facebook_url,
                $note->swarm_url,
                $note->mastodon_url,
                $note->bluesky_url,
            ])),
            'location' => $this->location($note),
        ], fn (array $values) => $values !== []);
    }

    private function location(Note $note): array
    {
        if ($note->place !== null) {
            return [$note->place->uri];
        }

        if ($note->getRawOriginal('location') !== null) {
            return ['geo:'.$note->latitude.','.$note->longitude];
        }

        return [];
    }
}
