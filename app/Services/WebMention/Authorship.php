<?php

declare(strict_types=1);

namespace App\Services\WebMention;

use App\Exceptions\AuthorshipParserException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

use function Mf2\parse;

/**
 * Determine the author of a post from its parsed microformats, following
 * the IndieWeb authorship algorithm: https://indieweb.org/authorship
 *
 * Originally from the jonnybarnes/webmentions-parser package.
 */
class Authorship
{
    /**
     * Resolve the author to a single h-card shape, suitable for storing.
     *
     * A plain-name author becomes an h-card with just a name, and an author
     * that can't be determined (including when parsing fails) becomes null.
     */
    public function resolve(array $mf): ?array
    {
        try {
            $author = $this->findAuthor($mf);
        } catch (AuthorshipParserException) {
            return null;
        }

        if (is_string($author)) {
            $name = trim($author);

            return $name === '' ? null : [
                'type' => ['h-card'],
                'properties' => ['name' => [$name]],
            ];
        }

        if (! is_array($author) || ! is_array($author['properties'] ?? null)) {
            return null;
        }

        return $author;
    }

    /**
     * Find the author of the first h-entry (or h-feed) in the microformats.
     *
     * Returns the author's h-card, or their name as a string if that's all
     * we have, or false if the author can't be determined.
     *
     * @throws AuthorshipParserException
     */
    public function findAuthor(array $mf, bool $permalink = true): array|string|false
    {
        $hEntry = null;
        $hFeed = null;
        foreach ($mf['items'] ?? [] as $item) {
            foreach ($item['type'] ?? [] as $type) {
                if ($type === 'h-entry') {
                    $hEntry = $item;
                } elseif ($type === 'h-feed') {
                    $hFeed = $item;
                }
            }
        }

        if ($hEntry === null && $hFeed === null) {
            throw new AuthorshipParserException('No h-entry or h-feed found');
        }

        // If the h-entry has an author property use that
        $author = $hEntry['properties']['author'] ?? null;

        // A parent h-feed with an h-card takes precedence
        foreach ($hFeed['children'] ?? [] as $child) {
            if (($child['type'][0] ?? null) === 'h-card') {
                $author = $child;
            }
        }

        $authorPage = null;

        if ($author !== null) {
            // Flatten a single string entry
            if (is_array($author) && count($author) === 1 && is_string($author[0] ?? null)) {
                $author = $author[0];
            }

            // A URL author is the author-page, anything else is the author itself
            if (is_string($author) && filter_var($author, FILTER_VALIDATE_URL)) {
                $authorPage = $author;
            } else {
                return $this->normalise($author);
            }
        }

        // If no author-page and h-entry is a permalink then use the rel-author link
        if ($authorPage === null && $permalink && isset($mf['rels']['author'])) {
            $authorPage = is_array($mf['rels']['author']) ? $mf['rels']['author'][0] : $mf['rels']['author'];
        }

        if ($authorPage === null) {
            return false;
        }

        return $this->findAuthorOnPage($authorPage);
    }

    /**
     * Fetch the author-page and look for a representative h-card on it.
     *
     * @throws AuthorshipParserException
     */
    private function findAuthorOnPage(string $authorPage): array|false
    {
        try {
            $response = Http::get($authorPage);
        } catch (ConnectionException) {
            throw new AuthorshipParserException('Unable to connect to the author’s page');
        }

        if ($response->failed()) {
            throw new AuthorshipParserException('Unable to get the content from the author’s page');
        }

        $authorMf2 = parse($response->body(), $authorPage);
        $hCards = array_filter(
            $authorMf2['items'] ?? [],
            fn (array $item): bool => in_array('h-card', $item['type'] ?? [], true)
        );

        // Use the first h-card where url == uid == author-page
        foreach ($hCards as $hCard) {
            if (
                in_array($authorPage, $hCard['properties']['url'] ?? [], true)
                && in_array($authorPage, $hCard['properties']['uid'] ?? [], true)
            ) {
                return $this->normalise($hCard);
            }
        }

        // Else the first h-card with a url that matches a rel-me link on the page
        $relMeLinks = $authorMf2['rels']['me'] ?? [];
        foreach ($hCards as $hCard) {
            foreach ($hCard['properties']['url'] ?? [] as $url) {
                if (in_array($url, $relMeLinks, true)) {
                    return $this->normalise($hCard);
                }
            }
        }

        // Else the first h-card with url == author-page
        foreach ($hCards as $hCard) {
            if (in_array($authorPage, $hCard['properties']['url'] ?? [], true)) {
                return $this->normalise($hCard);
            }
        }

        return false;
    }

    private function normalise(array|string $author): array|string
    {
        if (is_array($author) && array_key_exists(0, $author)) {
            $author = $author[0];
        }

        if (is_string($author)) {
            return $author;
        }

        unset($author['value'], $author['children']);

        return $author;
    }
}
