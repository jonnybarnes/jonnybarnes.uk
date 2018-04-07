<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Note;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWebMentions implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $note;

    /**
     * Create the job instance, inject dependencies.
     *
     * @param  \App\Models\Note  $note
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //grab the URLs
        $inReplyTo = $this->note->in_reply_to ?? '';
        // above so explode doesn’t complain about null being passed in
        $urlsInReplyTo = explode(' ', $inReplyTo);
        $urlsNote = $this->getLinks($this->note->note);
        $urls = array_filter(array_merge($urlsInReplyTo, $urlsNote)); //filter out none URLs
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
     * @param  string  $url
     * @return string|null
     */
    public function discoverWebmentionEndpoint(string $url): ?string
    {
        //let’s not send webmentions to myself
        if (parse_url($url, PHP_URL_HOST) == config('app.longurl')) {
            return null;
        }
        if (starts_with($url, '/notes/tagged/')) {
            return null;
        }

        $endpoint = null;

        $guzzle = resolve(Client::class);
        $response = $guzzle->get($url);
        //check HTTP Headers for webmention endpoint
        $links = \GuzzleHttp\Psr7\parse_header($response->getHeader('Link'));
        foreach ($links as $link) {
            if (mb_stristr($link['rel'], 'webmention')) {
                return $this->resolveUri(trim($link[0], '<>'), $url);
            }
        }

        //failed to find a header so parse HTML
        $html = (string) $response->getBody();

        $mf2 = new \Mf2\Parser($html, $url);
        $rels = $mf2->parseRelsAndAlternates();
        if (is_array($rels) && array_key_exists('0', $rels)) {
            if (array_key_exists('webmention', $rels[0])) {
                $endpoint = $rels[0]['webmention'][0];
            } elseif (array_key_exists('http://webmention.org/', $rels[0])) {
                $endpoint = $rels[0]['http://webmention.org/'][0];
            }
        }
        if ($endpoint) {
            return $this->resolveUri($endpoint, $url);
        }

        return null;
    }

    /**
     * Get the URLs from a note.
     *
     * @param  string  $html
     * @return array $urls
     */
    public function getLinks(string $html): array
    {
        if ($html == '' || is_null($html)) {
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
     *
     * @param  string  $url
     * @param  string  $base The base of the URL
     * @return string
     */
    public function resolveUri(string $url, string $base): string
    {
        $endpoint = \GuzzleHttp\Psr7\uri_for($url);
        if ($endpoint->getScheme() != '') {
            return (string) $endpoint;
        }

        return (string) \GuzzleHttp\Psr7\Uri::resolve(
            \GuzzleHttp\Psr7\uri_for($base),
            $endpoint
        );
    }
}
