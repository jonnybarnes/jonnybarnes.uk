<?php

namespace App\Observers;

use App\Models\{Note, Tag};

class NoteObserver
{
    /**
     * Listen to the Note created event.
     *
     * @param  \App\Note  $note
     * @return void
     */
    public function created(Note $note)
    {
        $tags = $this->getTagsFromNote($note->getAttributes()['note']);

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
     * @param  \App\Note  $Note
     * @return void
     */
    public function updated(Note $note)
    {
        $tags = $this->getTagsFromNote($note->getAttributes()['note']);
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
     * @param  \App\Note  $note
     * @return void
     */
    public function deleting(Note $note)
    {
        $note->tags()->detach();
    }

    public function getTagsFromNote($note)
    {
        preg_match_all('/#([^\s<>]+)\b/', $note, $tags);

        return collect($tags[1])->map(function ($tag) {
            return Tag::normalize($tag);
        })->unique();
    }
}
