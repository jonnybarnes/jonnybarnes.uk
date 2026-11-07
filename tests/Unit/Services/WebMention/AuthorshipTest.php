<?php

declare(strict_types=1);

namespace Tests\Unit\Services\WebMention;

use App\Exceptions\AuthorshipParserException;
use App\Services\WebMention\Authorship;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorshipTest extends TestCase
{
    private const string AUTHOR_PAGE = 'https://fred.example/';

    private function entryBy(mixed $author, array $rels = []): array
    {
        return [
            'items' => [[
                'type' => ['h-entry'],
                'properties' => ['content' => ['A post'], 'author' => [$author]],
            ]],
            'rels' => $rels,
        ];
    }

    private function hCard(string $name, array $extraProperties = []): array
    {
        return [
            'type' => ['h-card'],
            'properties' => array_merge(['name' => [$name], 'url' => [self::AUTHOR_PAGE]], $extraProperties),
            'value' => $name,
        ];
    }

    #[Test]
    public function it_returns_an_embedded_author_h_card(): void
    {
        $author = (new Authorship)->findAuthor($this->entryBy($this->hCard('Fred Bloggs')));

        $this->assertSame('Fred Bloggs', $author['properties']['name'][0]);
        $this->assertArrayNotHasKey('value', $author);
    }

    #[Test]
    public function it_returns_a_plain_author_name(): void
    {
        $this->assertSame('Fred Bloggs', (new Authorship)->findAuthor($this->entryBy('Fred Bloggs')));
    }

    #[Test]
    public function it_uses_an_h_card_in_the_parent_h_feed(): void
    {
        $mf = ['items' => [[
            'type' => ['h-feed'],
            'properties' => [],
            'children' => [
                ['type' => ['h-entry'], 'properties' => ['content' => ['A post']]],
                $this->hCard('Feed Author'),
            ],
        ]], 'rels' => []];

        $author = (new Authorship)->findAuthor($mf);

        $this->assertSame('Feed Author', $author['properties']['name'][0]);
    }

    #[Test]
    public function it_fetches_a_url_author_and_uses_the_h_card_whose_url_and_uid_match(): void
    {
        Http::fake([self::AUTHOR_PAGE => Http::response(<<<'HTML'
            <div class="h-card"><a class="p-name u-url" href="https://fred.example/">Not the uid one</a></div>
            <div class="h-card"><a class="p-name u-url u-uid" href="https://fred.example/">Fred Bloggs</a></div>
            HTML)]);

        $author = (new Authorship)->findAuthor($this->entryBy(self::AUTHOR_PAGE));

        $this->assertSame('Fred Bloggs', $author['properties']['name'][0]);
    }

    #[Test]
    public function it_fetches_a_url_author_and_uses_the_h_card_matching_a_rel_me_link(): void
    {
        Http::fake([self::AUTHOR_PAGE => Http::response(<<<'HTML'
            <link rel="me" href="https://social.example/@fred">
            <div class="h-card"><span class="p-name">Someone Else</span><a class="u-url" href="https://elsewhere.example/"></a></div>
            <div class="h-card"><span class="p-name">Fred Bloggs</span><a class="u-url" href="https://social.example/@fred"></a></div>
            HTML)]);

        $author = (new Authorship)->findAuthor($this->entryBy(self::AUTHOR_PAGE));

        $this->assertSame('Fred Bloggs', $author['properties']['name'][0]);
    }

    #[Test]
    public function it_fetches_a_url_author_and_uses_the_h_card_whose_url_is_the_author_page(): void
    {
        Http::fake([self::AUTHOR_PAGE => Http::response(<<<'HTML'
            <div class="h-card"><a class="p-name u-url" href="https://elsewhere.example/">Someone Else</a></div>
            <div class="h-card"><a class="p-name u-url" href="https://fred.example/">Fred Bloggs</a></div>
            HTML)]);

        $author = (new Authorship)->findAuthor($this->entryBy(self::AUTHOR_PAGE));

        $this->assertSame('Fred Bloggs', $author['properties']['name'][0]);
    }

    #[Test]
    public function it_falls_back_to_the_rel_author_link(): void
    {
        Http::fake([self::AUTHOR_PAGE => Http::response(
            '<div class="h-card"><a class="p-name u-url" href="https://fred.example/">Fred Bloggs</a></div>'
        )]);
        $mf = ['items' => [['type' => ['h-entry'], 'properties' => ['content' => ['A post']]]], 'rels' => ['author' => [self::AUTHOR_PAGE]]];

        $author = (new Authorship)->findAuthor($mf);

        $this->assertSame('Fred Bloggs', $author['properties']['name'][0]);
    }

    #[Test]
    public function it_ignores_the_rel_author_link_when_not_a_permalink(): void
    {
        Http::fake();
        $mf = ['items' => [['type' => ['h-entry'], 'properties' => ['content' => ['A post']]]], 'rels' => ['author' => [self::AUTHOR_PAGE]]];

        $this->assertFalse((new Authorship)->findAuthor($mf, permalink: false));
        Http::assertNothingSent();
    }

    #[Test]
    public function it_returns_false_when_no_author_can_be_found(): void
    {
        Http::fake([self::AUTHOR_PAGE => Http::response('<p>No h-card here</p>')]);

        $this->assertFalse((new Authorship)->findAuthor($this->entryBy(self::AUTHOR_PAGE)));
        $this->assertFalse((new Authorship)->findAuthor(['items' => [['type' => ['h-entry'], 'properties' => []]], 'rels' => []]));
    }

    #[Test]
    public function it_throws_without_an_h_entry_or_h_feed(): void
    {
        $this->expectException(AuthorshipParserException::class);

        (new Authorship)->findAuthor(['items' => [$this->hCard('Fred Bloggs')], 'rels' => []]);
    }

    #[Test]
    public function it_throws_when_the_author_page_cannot_be_fetched(): void
    {
        Http::fake([self::AUTHOR_PAGE => Http::response('Not found', 404)]);

        $this->expectException(AuthorshipParserException::class);

        (new Authorship)->findAuthor($this->entryBy(self::AUTHOR_PAGE));
    }

    #[Test]
    public function one_instance_can_be_reused_without_leaking_state(): void
    {
        $authorship = new Authorship;

        $this->assertSame('Fred Bloggs', $authorship->findAuthor($this->entryBy('Fred Bloggs')));
        $this->assertFalse($authorship->findAuthor(['items' => [['type' => ['h-entry'], 'properties' => []]], 'rels' => []]));
    }
}
