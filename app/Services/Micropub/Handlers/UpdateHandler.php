<?php

declare(strict_types=1);

namespace App\Services\Micropub\Handlers;

use App\Exceptions\InvalidTokenScopeException;
use App\Exceptions\MicropubHandlerException;
use App\Exceptions\MicropubUnsupportedModelException;
use App\Models\Media;
use App\Models\Note;
use App\Models\SyndicationTarget;
use App\Services\Micropub\Data\MicropubData;
use App\Services\Micropub\Data\UpdateData;
use App\Services\Micropub\NoteResolver;
use Illuminate\Support\Str;

class UpdateHandler implements MicropubHandlerInterface
{
    /**
     * The properties each update operation can change.
     */
    private const SUPPORTED_PROPERTIES = [
        'replace' => ['content', 'syndication'],
        'add' => ['syndication', 'photo'],
        'delete' => ['syndication', 'photo'],
    ];

    /**
     * The syndication URLs we have somewhere to store. Mastodon URLs are also
     * accepted, based on the instances in our syndication targets.
     */
    private const SYNDICATION_PREFIXES = [
        'https://www.facebook.com',
        'https://www.swarmapp.com',
        'https://twitter.com',
        'https://bsky.app',
    ];

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
        $scopes = explode(' ', $scopes);

        if (! in_array('update', $scopes, true)) {
            throw new InvalidTokenScopeException;
        }

        if ($data->updateUrl === null) {
            throw new MicropubHandlerException('The url parameter is required');
        }

        $note = resolve(NoteResolver::class)->fromUrl($data->updateUrl);

        if (! $data->updateReplace && ! $data->updateAdd && ! $data->updateDelete) {
            throw new MicropubHandlerException('Unsupported update operation');
        }

        $this->validateOperations($data);

        if ($data->updateReplace !== null) {
            foreach ($data->updateReplace as $property => $value) {
                match ($property) {
                    'content' => $note->note = is_array($value[0]) ? ($value[0]['html'] ?? $value[0]['value'] ?? null) : $value[0],
                    'syndication' => $this->applySyndication($note, $value),
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
                        $media = new Media;
                        $media->path = $photoURL;
                        $media->type = 'image';
                        $media->save();
                        $note->media()->save($media);
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

    /**
     * Check the whole request before applying any of it, so that a bad value
     * can't leave a note partially updated, and a client is never told an
     * update worked when we ignored it. The spec requires the values of each
     * property to be arrays.
     *
     * @throws MicropubHandlerException
     */
    private function validateOperations(UpdateData $data): void
    {
        foreach (['replace' => $data->updateReplace ?? [], 'add' => $data->updateAdd ?? []] as $operation => $properties) {
            foreach ($properties as $property => $values) {
                if (! is_string($property) || ! is_array($values) || $values === [] || ! array_is_list($values)) {
                    throw new MicropubHandlerException('The values of each property must be a non-empty array');
                }

                $this->validateProperty($operation, $property, $values);
            }
        }

        $delete = $data->updateDelete ?? [];

        if (array_is_list($delete)) {
            foreach ($delete as $property) {
                if (! is_string($property)) {
                    throw new MicropubHandlerException('The properties to delete must be strings');
                }

                $this->validateProperty('delete', $property, []);
            }

            return;
        }

        foreach ($delete as $property => $values) {
            if (! is_array($values) || ! array_is_list($values)) {
                throw new MicropubHandlerException('The values of each property must be an array');
            }

            $this->validateProperty('delete', $property, $values);
        }
    }

    /**
     * @throws MicropubHandlerException
     */
    private function validateProperty(string $operation, string $property, array $values): void
    {
        if (! in_array($property, self::SUPPORTED_PROPERTIES[$operation], true)) {
            throw new MicropubHandlerException("This server does not support the {$operation} operation on the {$property} property");
        }

        foreach ($values as $value) {
            if ($property === 'content' && is_array($value)) {
                $value = $value['html'] ?? $value['value'] ?? null;
            }

            if (! is_string($value)) {
                throw new MicropubHandlerException("The {$property} property has an invalid value");
            }

            if ($property === 'syndication' && ! Str::startsWith($value, [...self::SYNDICATION_PREFIXES, ...$this->mastodonPrefixes()])) {
                throw new MicropubHandlerException("This server can not store the syndication URL {$value}");
            }

            if ($operation === 'add' && $property === 'photo' && ! Str::startsWith($value, 'https://')) {
                throw new MicropubHandlerException('Photos must be added using an https URL');
            }
        }
    }

    private function applyDelete(Note $note, array $delete): void
    {
        if (array_is_list($delete)) {
            foreach ($delete as $property) {
                match ($property) {
                    'syndication' => $this->clearSyndication($note),
                    'photo' => $note->media()->delete(),
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

    /**
     * Mastodon can be on any host, so use the instances we syndicate to.
     *
     * @return string[]
     */
    private function mastodonPrefixes(): array
    {
        return SyndicationTarget::where('service_name', 'Mastodon')
            ->pluck('uid')
            ->map(fn (string $uid) => parse_url($uid, PHP_URL_HOST))
            ->filter()
            ->map(fn (string $host) => "https://{$host}/")
            ->all();
    }

    private function clearSyndication(Note $note): void
    {
        $note->facebook_url = null;
        $note->swarm_url = null;
        $note->tweet_id = null;
        $note->mastodon_url = null;
        $note->bluesky_url = null;
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
            } elseif (Str::startsWith($url, $this->mastodonPrefixes()) && $note->mastodon_url === $url) {
                $note->mastodon_url = null;
            } elseif (Str::startsWith($url, 'https://bsky.app') && $note->bluesky_url === $url) {
                $note->bluesky_url = null;
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
            } elseif (Str::startsWith($url, $this->mastodonPrefixes())) {
                $note->mastodon_url = $url;
            } elseif (Str::startsWith($url, 'https://bsky.app')) {
                $note->bluesky_url = $url;
            }
        }
    }
}
