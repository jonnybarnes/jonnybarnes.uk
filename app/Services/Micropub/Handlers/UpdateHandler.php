<?php

declare(strict_types=1);

namespace App\Services\Micropub\Handlers;

use App\Exceptions\InvalidTokenScopeException;
use App\Exceptions\MicropubHandlerException;
use App\Exceptions\MicropubUnsupportedModelException;
use App\Models\Media;
use App\Models\Note;
use App\Services\Micropub\Data\MicropubData;
use App\Services\Micropub\Data\UpdateData;
use Illuminate\Support\Str;

class UpdateHandler implements MicropubHandlerInterface
{
    public function dataClass(): string
    {
        return UpdateData::class;
    }

    /**
     * @throws InvalidTokenScopeException
     * @throws MicropubUnsupportedModelException
     * @throws MicropubHandlerException
     */
    public function handle(MicropubData $data): array
    {
        assert($data instanceof UpdateData);

        $scopes = $data->tokenData['scope'];
        if (is_string($scopes)) {
            $scopes = explode(' ', $scopes);
        }

        if (! in_array('update', $scopes, true)) {
            throw new InvalidTokenScopeException;
        }

        $urlPath = parse_url($data->updateUrl, PHP_URL_PATH);

        if (mb_substr($urlPath, 1, 5) !== 'notes') {
            throw new MicropubUnsupportedModelException('This implementation currently only supports the updating of notes');
        }

        $note = Note::nb60(basename($urlPath))->firstOrFail();

        if ($data->updateReplace !== null) {
            foreach ($data->updateReplace as $property => $value) {
                if ($property === 'content') {
                    $note->note = $value[0];
                }
                if ($property === 'syndication') {
                    $this->applySyndication($note, $value);
                }
            }
            $note->save();

            return [
                'response' => 'updated',
                'url' => $note->uri,
            ];
        }

        if ($data->updateAdd !== null) {
            foreach ($data->updateAdd as $property => $value) {
                if ($property === 'syndication') {
                    $this->applySyndication($note, $value);
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

            return [
                'response' => 'updated',
                'url' => $note->uri,
            ];
        }

        throw new MicropubHandlerException('Unsupported update operation');
    }

    private function applySyndication(Note $note, array $urls): void
    {
        foreach ($urls as $url) {
            if (Str::startsWith($url, 'https://www.facebook.com')) {
                $note->facebook_url = $url;
            } elseif (Str::startsWith($url, 'https://www.swarmapp.com')) {
                $note->swarm_url = $url;
            } elseif (Str::startsWith($url, 'https://twitter.com')) {
                $note->tweet_id = basename(parse_url($url, PHP_URL_PATH));
            }
        }
    }
}
