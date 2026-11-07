<?php

declare(strict_types=1);

namespace App\Services\Micropub\Data;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class EntryData extends MicropubData
{
    public function __construct(
        public readonly array $tokenData,
        public readonly ?string $content,
        public readonly ?string $inReplyTo,
        public readonly ?string $published,
        public readonly mixed $location,
        public readonly ?string $bookmarkOf,
        public readonly ?string $likeOf,
        public readonly ?array $mpSyndicateTo,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly mixed $geo,
        public readonly mixed $checkin,
        public readonly mixed $syndication,
        public readonly ?array $photos,
        public readonly ?string $postStatus,
        public readonly ?array $category,
    ) {}

    public static function fromRequest(Request $request): static
    {
        if ($request->isJson()) {
            $data = $request->json()->all();

            $rawContent = Arr::get($data, 'properties.content.0');
            $content = is_array($rawContent) ? ($rawContent['html'] ?? $rawContent['value'] ?? null) : $rawContent;

            return new static(
                tokenData: $data['token_data'],
                content: $content,
                inReplyTo: Arr::get($data, 'properties.in-reply-to.0'),
                published: Arr::get($data, 'properties.published.0'),
                location: self::extractLocationData($data),
                bookmarkOf: Arr::get($data, 'properties.bookmark-of.0'),
                likeOf: Arr::get($data, 'properties.like-of.0'),
                mpSyndicateTo: Arr::get($data, 'properties.mp-syndicate-to'),
                name: Arr::get($data, 'properties.name.0'),
                description: Arr::get($data, 'properties.description.0'),
                geo: Arr::get($data, 'properties.geo.0'),
                checkin: Arr::get($data, 'properties.checkin.0'),
                syndication: Arr::get($data, 'properties.syndication.0'),
                photos: Arr::get($data, 'properties.photo'),
                postStatus: Arr::get($data, 'properties.post-status.0'),
                category: Arr::get($data, 'properties.category'),
            );
        }

        return new static(
            tokenData: $request->input('token_data'),
            content: $request->input('content'),
            inReplyTo: $request->input('in-reply-to'),
            published: $request->input('published'),
            location: $request->input('location'),
            bookmarkOf: $request->input('bookmark-of'),
            likeOf: $request->input('like-of'),
            mpSyndicateTo: self::wrapFormValue($request->input('mp-syndicate-to')),
            name: $request->input('name'),
            description: $request->input('description'),
            geo: $request->input('geo'),
            checkin: $request->input('checkin'),
            syndication: $request->input('syndication'),
            photos: self::wrapFormValue($request->input('photo')),
            postStatus: $request->input('post-status'),
            category: self::wrapFormValue($request->input('category')),
        );
    }

    /**
     * Form-encoded multi-value properties may be sent with or without the []
     * suffix, so a single value arrives as a string rather than an array.
     */
    private static function wrapFormValue(mixed $value): ?array
    {
        return $value === null ? null : Arr::wrap($value);
    }

    private static function extractLocationData(array $data): array|string|null
    {
        if (! Arr::has($data, 'properties.location')) {
            return null;
        }

        if (Arr::has($data, 'properties.location.0')) {
            return Arr::get($data, 'properties.location.0');
        }

        return Arr::get($data, 'properties.location');
    }

    public static function fromArray(array $data): static
    {
        return new static(
            tokenData: $data['token_data'],
            content: $data['content'] ?? null,
            inReplyTo: $data['in-reply-to'] ?? null,
            published: $data['published'] ?? null,
            location: $data['location'] ?? null,
            bookmarkOf: $data['bookmark-of'] ?? null,
            likeOf: $data['like-of'] ?? null,
            mpSyndicateTo: $data['mp-syndicate-to'] ?? null,
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            geo: $data['geo'] ?? null,
            checkin: $data['checkin'] ?? null,
            syndication: $data['syndication'] ?? null,
            photos: $data['photos'] ?? null,
            postStatus: $data['post-status'] ?? null,
            category: $data['category'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'token_data' => $this->tokenData,
            'content' => $this->content,
            'in-reply-to' => $this->inReplyTo,
            'published' => $this->published,
            'location' => $this->location,
            'bookmark-of' => $this->bookmarkOf,
            'like-of' => $this->likeOf,
            'mp-syndicate-to' => $this->mpSyndicateTo,
            'name' => $this->name,
            'description' => $this->description,
            'geo' => $this->geo,
            'checkin' => $this->checkin,
            'syndication' => $this->syndication,
            'photos' => $this->photos,
            'post-status' => $this->postStatus,
            'category' => $this->category,
        ];
    }
}
