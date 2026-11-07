<?php

declare(strict_types=1);

namespace Tests\Unit\Services\WebMention;

use App\Exceptions\InvalidMentionException;
use App\Services\WebMention\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ParserTest extends TestCase
{
    private const string TARGET = 'https://jonnybarnes.uk/notes/A';

    private function entry(array $properties): array
    {
        return ['items' => [['type' => ['h-entry'], 'properties' => $properties]]];
    }

    public static function mentionTypes(): array
    {
        return [
            'reply' => ['in-reply-to'],
            'like' => ['like-of'],
            'repost' => ['repost-of'],
        ];
    }

    #[Test]
    #[DataProvider('mentionTypes')]
    public function it_determines_the_mention_type(string $type): void
    {
        $this->assertSame($type, (new Parser)->getMentionType($this->entry([$type => [self::TARGET]])));
    }

    #[Test]
    public function it_finds_the_mention_type_in_nested_microformats(): void
    {
        $mf = ['items' => [['type' => ['h-feed'], 'children' => [
            ['type' => ['h-entry'], 'properties' => ['like-of' => [self::TARGET]]],
        ]]]];

        $this->assertSame('like-of', (new Parser)->getMentionType($mf));
    }

    #[Test]
    public function it_throws_when_the_mention_type_cannot_be_determined(): void
    {
        $this->expectException(InvalidMentionException::class);

        (new Parser)->getMentionType($this->entry(['content' => ['Just a post']]));
    }

    #[Test]
    public function it_checks_a_reply_url_matches_the_target(): void
    {
        $parser = new Parser;

        $this->assertTrue($parser->checkInReplyTo($this->entry(['in-reply-to' => [self::TARGET]]), self::TARGET));
        $this->assertFalse($parser->checkInReplyTo($this->entry(['in-reply-to' => ['https://example.org/other']]), self::TARGET));
        $this->assertFalse($parser->checkInReplyTo($this->entry(['content' => ['No reply']]), self::TARGET));
    }

    #[Test]
    public function it_checks_a_reply_context_h_cite_matches_the_target(): void
    {
        $hCite = fn (string $url): array => ['type' => ['h-cite'], 'properties' => ['url' => [$url]]];
        $parser = new Parser;

        $this->assertTrue($parser->checkInReplyTo($this->entry(['in-reply-to' => [$hCite(self::TARGET)]]), self::TARGET));
        $this->assertFalse($parser->checkInReplyTo($this->entry(['in-reply-to' => [$hCite('https://example.org/other')]]), self::TARGET));
    }

    #[Test]
    public function it_checks_likes_and_reposts_match_the_target(): void
    {
        $parser = new Parser;

        $this->assertTrue($parser->checkLikeOf($this->entry(['like-of' => [self::TARGET]]), self::TARGET));
        $this->assertFalse($parser->checkLikeOf($this->entry(['like-of' => ['https://example.org/other']]), self::TARGET));
        $this->assertFalse($parser->checkLikeOf($this->entry(['repost-of' => [self::TARGET]]), self::TARGET));

        $this->assertTrue($parser->checkRepostOf($this->entry(['repost-of' => [self::TARGET]]), self::TARGET));
        $this->assertFalse($parser->checkRepostOf($this->entry(['repost-of' => ['https://example.org/other']]), self::TARGET));
        $this->assertFalse($parser->checkRepostOf($this->entry(['like-of' => [self::TARGET]]), self::TARGET));
    }
}
