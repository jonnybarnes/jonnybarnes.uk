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

        if ($data->updateReplace === null && $data->updateAdd === null && $data->updateDelete === null) {
            throw new MicropubHandlerException('Unsupported update operation');
        }

        if ($data->updateReplace !== null) {
            foreach ($data->updateReplace as $property => $value) {
                match ($property) {
                    'content'     => $note->note = $value[0],
                    'syndication' => $this->applySyndication($note, $value),
                    default       => null,
                };
            }
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
        }

        if ($data->updateDelete !== null) {
            $this->applyDelete($note, $data->updateDelete);
        }

        $note->save();

        return [
            'response' => 'updated',
            'url' => $note->uri,
        ];
    }

    private function applyDelete(Note $note, array $delete): void
    {
        if (array_is_list($delete)) {
            foreach ($delete as $property) {
                match ($property) {
                    'syndication' => $this->clearSyndication($note),
                    'photo'       => $note->media()->delete(),
                    default       => null,
                };
            }

            return;
        }

        foreach ($delete as $property => $values) {
            if ($property === 'syndication') {
                $this->removeSyndicationValues($note, $values);
            }
            if ($property === 'photo') {
                $note->media()->whereIn('path', $values)->delete();
            }
        }
    }

    private function clearSyndication(Note $note): void
    {
        $note->facebook_url = null;
        $note->swarm_url = null;
        $note->tweet_id = null;
    }

    private function removeSyndicationValues(Note $note, array $urls): void
    {
        foreach ($urls as $url) {
            if (Str::startsWith($url, 'https://www.facebook.com') && $note->facebook_url === $url) {
                $note->facebook_url = null;
            } elseif (Str::startsWith($url, 'https://www.swarmapp.com') && $note->swarm_url === $url) {
                $note->swarm_url = null;
            } elseif (Str::startsWith($url, 'https://twitter.com')) {
                $note->tweet_id = null;
            }
        }
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
