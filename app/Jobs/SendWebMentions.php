<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Note;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
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

    /** @var Note */
    protected $note;

    /**
     * Create the job instance, inject dependencies.
     *
     * @param Note $note
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
     * @param string $url
     * @return string|null
     */
    public function discoverWebmentionEndpoint(string $url)
    {
        //let’s not send webmentions to myself
        if (parse_url($url, PHP_URL_HOST) == config('app.longurl')) {
            return;
        }
        if (Str::startsWith($url, '/notes/tagged/')) {
            return;
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
        if (array_key_exists('webmention', $rels[0])) {
            $endpoint = $rels[0]['webmention'][0];
        } elseif (array_key_exists('http://webmention.org/', $rels[0])) {
            $endpoint = $rels[0]['http://webmention.org/'][0];
        }
        if ($endpoint) {
            return $this->resolveUri($endpoint, $url);
        }
    }

    /**
     * Get the URLs from a note.
     *
     * @param string|null $html
     * @return array
     */
    public function getLinks(?string $html): array
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
     * @todo Update deprecated resolve method
     *
     * @param string $url
     * @param string $base The base of the URL
     * @return string
     */
    public function resolveUri(string $url, string $base): string
    {
        $endpoint = \GuzzleHttp\Psr7\uri_for($url);
        if ($endpoint->getScheme() != '') {
            return (string) $endpoint;
        }

        return (string) Uri::resolve(
            \GuzzleHttp\Psr7\uri_for($base),
            $endpoint
        );
    }
}
