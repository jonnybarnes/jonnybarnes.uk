<?php

declare(strict_types=1);

namespace App\Services\Micropub\Data;

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
    ) {}

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
        ];
    }
}
