<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use App\Exceptions\InvalidTokenScopeException;
use App\Models\Media;
use App\Models\Note;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/*
 * @todo Implement this properly
 */
class UpdateHandler implements MicropubHandlerInterface
{
    /**
     * @throws InvalidTokenScopeException
     */
    public function handle(array $data)
    {
        $scopes = $data['token_data']['scope'];
        if (is_string($scopes)) {
            $scopes = explode(' ', $scopes);
        }

        if (! in_array('update', $scopes, true)) {
            throw new InvalidTokenScopeException;
        }

        $urlPath = parse_url(Arr::get($data, 'url'), PHP_URL_PATH);

        // is it a note we are updating?
        if (mb_substr($urlPath, 1, 5) !== 'notes') {
            return response()->json([
                'error' => 'invalid',
                'error_description' => 'This implementation currently only support the updating of notes',
            ], 500);
        }

        try {
            $note = Note::nb60(basename($urlPath))->firstOrFail();
        } catch (ModelNotFoundException) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'No known note with given ID',
            ], 404);
        }

        // got the note, are we dealing with a “replace” request?
        if (Arr::get($data, 'replace')) {
            foreach (Arr::get($data, 'replace') as $property => $value) {
                if ($property === 'content') {
                    $note->note = $value[0];
                }
                if ($property === 'syndication') {
                    foreach ($value as $syndicationURL) {
                        if (Str::startsWith($syndicationURL, 'https://www.facebook.com')) {
                            $note->facebook_url = $syndicationURL;
                        }
                        if (Str::startsWith($syndicationURL, 'https://www.swarmapp.com')) {
                            $note->swarm_url = $syndicationURL;
                        }
                        if (Str::startsWith($syndicationURL, 'https://twitter.com')) {
                            $note->tweet_id = basename(parse_url($syndicationURL, PHP_URL_PATH));
                        }
                    }
                }
            }
            $note->save();

            return [
                'response' => 'updated',
            ];
        }

        // how about “add”
        if (Arr::get($data, 'add')) {
            foreach (Arr::get($data, 'add') as $property => $value) {
                if ($property === 'syndication') {
                    foreach ($value as $syndicationURL) {
                        if (Str::startsWith($syndicationURL, 'https://www.facebook.com')) {
                            $note->facebook_url = $syndicationURL;
                        }
                        if (Str::startsWith($syndicationURL, 'https://www.swarmapp.com')) {
                            $note->swarm_url = $syndicationURL;
                        }
                        if (Str::startsWith($syndicationURL, 'https://twitter.com')) {
                            $note->tweet_id = basename(parse_url($syndicationURL, PHP_URL_PATH));
                        }
                    }
                }
                if ($property === 'photo') {
                    foreach ($value as $photoURL) {
                        if (Str::startsWith($photoURL, 'https://')) {
                            $media = new Media;
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

        return response()->json([
            'response' => 'error',
            'error_description' => 'unsupported request',
        ], 500);
    }
}
