<?php

namespace App\Services\Micropub;

use App\Note;
use App\Media;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateService
{
    public function process(Request $request)
    {
        $urlPath = parse_url($request->input('url'), PHP_URL_PATH);

        //is it a note we are updating?
        if (mb_substr($urlPath, 1, 5) !== 'notes') {
            return response()->json([
                'error' => 'invalid',
                'error_description' => 'This implementation currently only support the updating of notes',
            ], 500);
        }

        try {
            $note = Note::nb60(basename($urlPath))->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'No known note with given ID',
            ], 404);
        }

        //got the note, are we dealing with a “replace” request?
        if ($request->has('replace')) {
            foreach ($request->input('replace') as $property => $value) {
                if ($property == 'content') {
                    $note->note = $value[0];
                }
                if ($property == 'syndication') {
                    foreach ($value as $syndicationURL) {
                        if (starts_with($syndicationURL, 'https://www.facebook.com')) {
                            $note->facebook_url = $syndicationURL;
                        }
                        if (starts_with($syndicationURL, 'https://www.swarmapp.com')) {
                            $note->swarm_url = $syndicationURL;
                        }
                        if (starts_with($syndicationURL, 'https://twitter.com')) {
                            $note->tweet_id = basename(parse_url($syndicationURL, PHP_URL_PATH));
                        }
                    }
                }
            }
            $note->save();

            return response()->json([
                'response' => 'updated',
            ]);
        }

        //how about “add”
        if ($request->has('add')) {
            foreach ($request->input('add') as $property => $value) {
                if ($property == 'syndication') {
                    foreach ($value as $syndicationURL) {
                        if (starts_with($syndicationURL, 'https://www.facebook.com')) {
                            $note->facebook_url = $syndicationURL;
                        }
                        if (starts_with($syndicationURL, 'https://www.swarmapp.com')) {
                            $note->swarm_url = $syndicationURL;
                        }
                        if (starts_with($syndicationURL, 'https://twitter.com')) {
                            $note->tweet_id = basename(parse_url($syndicationURL, PHP_URL_PATH));
                        }
                    }
                }
                if ($property == 'photo') {
                    foreach ($value as $photoURL) {
                        if (start_with($photo, 'https://')) {
                            $media = new Media();
                            $media->path = $photoURL;
                            $media->type = 'image';
                            $media->save();
                            $note->media()->save($media);
                        }
                    }
                }
            }
            $note->save();

            return response()->json([
                'response' => 'updated',
            ]);
        }
    }
}
