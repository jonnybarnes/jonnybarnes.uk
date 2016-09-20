<?php

namespace App\Jobs;

use App\Note;
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
     * @param  Note $note
     * @return void
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    /**
     * Execute the job.
     *
     * @param  \GuzzleHttp\Client $guzzle
     * @return void
     */
    public function handle(Client $guzzle)
    {
        //grab the URLs
        $urlsInReplyTo = explode(' ', $this->note->in_reply_to);
        $urlsNote = $this->getLinks($this->note->note);
        $urls = array_filter(array_merge($urlsInReplyTo, $urlsNote)); //filter out none URLs
        foreach ($urls as $url) {
            $endpoint = $this->discoverWebmentionEndpoint($url, $guzzle);
            if ($endpoint) {
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
     * @param  string  The URL
     * @param  \GuzzleHttp\Client $guzzle
     * @return string  The webmention endpoint URL
     */
    private function discoverWebmentionEndpoint($url, $guzzle)
    {
        //let’s not send webmentions to myself
        if (parse_url($url, PHP_URL_HOST) == env('LONG_URL', 'localhost')) {
            return false;
        }
        if (starts_with($url, '/notes/tagged/')) {
            return false;
        }

        $endpoint = null;

        $response = $guzzle->get($url);
        //check HTTP Headers for webmention endpoint
        $links = \GuzzleHttp\Psr7\parse_header($response->getHeader('Link'));
        foreach ($links as $link) {
            if (mb_stristr($link['rel'], 'webmention')) {
                return $this->resolveUri($link[0], $url);
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
        if ($endpoint) {
            return $this->resolveUri($endpoint, $url);
        }

        return false;
    }

    /**
     * Get the URLs from a note.
     *
     * @param  string $html
     * @return array  $urls
     */
    public function getLinks($html)
    {
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
     * @param  string $url
     * @param  string $base
     * @return string
     */
    public function resolveUri(string $url, string $base): string
    {
        $endpoint = \GuzzleHttp\Psr7\uri_for($url);
        if ($endpoint->getScheme() !== null) {
            return (string) $endpoint;
        }

        return (string) \GuzzleHttp\Psr7\Uri::resolve(
            \GuzzleHttp\Psr7\uri_for($base),
            $endpoint
        );
    }
}
