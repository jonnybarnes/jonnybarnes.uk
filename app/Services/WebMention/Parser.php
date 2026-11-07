<?php

declare(strict_types=1);

namespace App\Services\WebMention;

use App\Exceptions\InvalidMentionException;

/**
 * Inspect the parsed microformats of a webmention source.
 *
 * Originally from the jonnybarnes/webmentions-parser package.
 */
class Parser
{
    /**
     * Return the type of mention.
     *
     * @throws InvalidMentionException when the mention type can't be determined
     */
    public function getMentionType(array $microformats): string
    {
        foreach (['in-reply-to', 'like-of', 'repost-of'] as $type) {
            if ($this->arrayKeyExistsRecursive($type, $microformats)) {
                return $type;
            }
        }

        throw new InvalidMentionException;
    }

    /**
     * Check a reply is to the intended target.
     */
    public function checkInReplyTo(array $microformats, string $target): bool
    {
        foreach ($microformats['items'] ?? [] as $item) {
            $inReplyTo = $item['properties']['in-reply-to'] ?? null;
            if ($inReplyTo === null) {
                continue;
            }

            // The reply context may be an h-cite rather than a plain URL
            if (is_array($inReplyTo[0]) && ($inReplyTo[0]['properties']['url'][0] ?? null) === $target) {
                return true;
            }

            if (in_array($target, $inReplyTo, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check the microformats contain a like of the target.
     */
    public function checkLikeOf(array $microformats, string $target): bool
    {
        return in_array($target, $microformats['items'][0]['properties']['like-of'] ?? [], true);
    }

    /**
     * Check the microformats contain a repost of the target.
     */
    public function checkRepostOf(array $microformats, string $target): bool
    {
        return in_array($target, $microformats['items'][0]['properties']['repost-of'] ?? [], true);
    }

    private function arrayKeyExistsRecursive(string $needle, array $haystack): bool
    {
        if (array_key_exists($needle, $haystack)) {
            return true;
        }

        foreach ($haystack as $value) {
            if (is_array($value) && $this->arrayKeyExistsRecursive($needle, $value)) {
                return true;
            }
        }

        return false;
    }
}
