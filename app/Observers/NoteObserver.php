<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Note;
use App\Models\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @todo Do we need psalm-suppress for these observer methods?
 */
class NoteObserver
{
    /**
     * Listen to the Note created event.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function created(Note $note): void
    {
        $text = Arr::get($note->getAttributes(), 'note');
        if ($text === null) {
            return;
        }
        $tags = $this->getTagsFromNote($text);

        if (count($tags) === 0) {
            return;
        }

        $tags->transform(function ($tag) {
            return Tag::firstOrCreate(['tag' => $tag])->id;
        })->toArray();

        $note->tags()->attach($tags);
    }

    /**
     * Listen to the Note updated event.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function updated(Note $note): void
    {
        $text = Arr::get($note->getAttributes(), 'note');
        if ($text === null) {
            return;
        }

        $tags = $this->getTagsFromNote($text);
        if (count($tags) === 0) {
            return;
        }

        $tags->transform(function ($tag) {
            return Tag::firstOrCreate(['tag' => $tag]);
        });

        $note->tags()->sync($tags->map(function ($tag) {
            return $tag->id;
        }));
    }

    /**
     * Listen to the Note deleting event.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function deleting(Note $note): void
    {
        $note->tags()->detach();
    }

    /**
     * Retrieve the tags from a note’s text, tag for form #tag.
     */
    private function getTagsFromNote(string $note): Collection
    {
        preg_match_all('/#([^\s<>]+)\b/', $note, $tags);

        return collect($tags[1])->map(function ($tag) {
            return Tag::normalize($tag);
        })->unique();
    }
}
