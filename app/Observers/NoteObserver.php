<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\{Note, Tag};
use Illuminate\Support\Collection;

class NoteObserver
{
    /**
     * Listen to the Note created event.
     *
     * @param  \App\Models\Note  $note
     */
    public function created(Note $note)
    {
        $text = array_get($note->getAttributes(), 'note');
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
     * @param  \App\Models\Note  $Note
     */
    public function updated(Note $note)
    {
        $text = array_get($note->getAttributes(), 'note');
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
     * @param  \App\Models\Note  $note
     */
    public function deleting(Note $note)
    {
        $note->tags()->detach();
    }

    /**
     * Retrieve the tags from a note’s text, tag for form #tag.
     *
     * @param  string  $note
     * @return \Illuminate\Support\Collection
     */
    private function getTagsFromNote(string $note): Collection
    {
        preg_match_all('/#([^\s<>]+)\b/', $note, $tags);

        return collect($tags[1])->map(function ($tag) {
            return Tag::normalize($tag);
        })->unique();
    }
}
