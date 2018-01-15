<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Note;

class ActivityStreamsService
{
    /**
     * Return the relevant data to an AS2.0 request to the root path.
     *
     * @return \Illuminate\Http\Response
     */
    public function siteOwnerResponse()
    {
        $data = json_encode([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => config('app.url'),
            'type' => 'Person',
            'name' => config('user.displayname'),
            'preferredUsername' => config('user.username'),
        ]);

        return response($data)->header('Content-Type', 'application/activity+json');
    }

    /**
     * Return the relevant data to an AS2.0 request for a particular note.
     *
     * @param  \App\Models\Note  $note
     * @return \Illuminate\Http\Response
     */
    public function singleNoteResponse(Note $note)
    {
        $data = json_encode([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'summary' => strtok(config('user.displayname'), ' ') . ' added a note to their microblog',
            'type' => 'Add',
            'published' => $note->updated_at->toW3cString(),
            'actor' => [
                'type' => 'Person',
                'id' => config('app.url'),
                'name' => config('app.display_name'),
                'url' => config('app.url'),
                'image' => [
                    'type' => 'Link',
                    'href' => config('app.url') . '/assets/img/profile.jpg',
                    'mediaType' => '/image/jpeg',
                ],
            ],
            'object' => [
                'id' => $note->longurl,
                'type' => 'Note',
                'url' => $note->longurl,
                'name' => strip_tags($note->note),
            ],
        ]);

        return response($data)->header('Content-Type', 'application/activity+json');
    }
}
