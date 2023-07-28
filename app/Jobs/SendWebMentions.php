<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Note;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Header;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendWebMentions implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Note $note
    ) {
    }

    /**
     * Execute the job.
     *
     * @throws GuzzleException
     */
    public function handle(): void
    {
        $urlsInReplyTo = explode(' ', $this->note->in_reply_to ?? '');
        $urlsNote = $this->getLinks($this->note->note);
        $urls = array_filter(array_merge($urlsInReplyTo, $urlsNote));
        foreach ($urls as $url) {
            $endpoint = $this->discoverWebmentionEndpoint($url);
            if ($endpoint !== null) {
                $guzzle = resolve(Client::class);
                $guzzle->post($endpoint, [
                    'form_params' => [
                        'source' => $this->note->longurl,
                        'target' => $url,
                    ],
                ]);
            }
        }
    }

    /**
     * Discover if a URL has a webmention endpoint.
     *
     * @throws GuzzleException
     */
    public function discoverWebmentionEndpoint(string $url): ?string
    {
        // let’s not send webmentions to myself
        if (parse_url($url, PHP_URL_HOST) === config('url.longurl')) {
            return null;
        }
        if (Str::startsWith($url, '/notes/tagged/')) {
            return null;
        }

        $endpoint = null;

        $guzzle = resolve(Client::class);
        $response = $guzzle->get($url);
        //check HTTP Headers for webmention endpoint
        $links = Header::parse($response->getHeader('Link'));
        foreach ($links as $link) {
            if (mb_stristr($link['rel'], 'webmention')) {
                return $this->resolveUri(trim($link[0], '<>'), $url);
            }
        }

        //failed to find a header so parse HTML
        $html = (string) $response->getBody();

        $mf2 = new \Mf2\Parser($html, $url);
        $rels = $mf2->parseRelsAndAlternates();
        if (array_key_exists('webmention', $rels[0])) {
            $endpoint = $rels[0]['webmention'][0];
        } elseif (array_key_exists('http://webmention.org/', $rels[0])) {
            $endpoint = $rels[0]['http://webmention.org/'][0];
        }

        if ($endpoint === null) {
            return null;
        }

        return $this->resolveUri($endpoint, $url);
    }

    /**
     * Get the URLs from a note.
     */
    public function getLinks(?string $html): array
    {
        if ($html === '' || is_null($html)) {
            return [];
        }

        $urls = [];
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $anchor) {
            $urls[] = ($anchor->hasAttribute('href')) ? $anchor->getAttribute('href') : false;
        }

        return $urls;
    }

    /**
     * Resolve a URI if necessary.
     */
    public function resolveUri(string $url, string $base): string
    {
        $endpoint = Utils::uriFor($url);
        if ($endpoint->getScheme() !== '') {
            return (string) $endpoint;
        }

        return (string) UriResolver::resolve(
            Utils::uriFor($base),
            $endpoint
        );
    }
}
