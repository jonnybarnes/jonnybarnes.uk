<?php

namespace App\Jobs;

use App\Note;
use GuzzleHttp\Client;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWebMentions extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $note;
    protected $guzzle;

    /**
     * Create the job instance, inject dependencies.
     *
     * @param  User $user
     * @param  Note $note
     * @return void
     */
    public function __construct(Note $note, Client $guzzle)
    {
        $this->note = $note;
        $this->guzzle = $guzzle ?? new Client();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Note $note)
    {
        //grab the URLs
        $urlsInReplyTo = explode(' ', $this->note->in_reply_to);
        $urlsNote = $this->getLinks($this->note->note);
        $urls = array_filter(array_merge($urlsInReplyTo, $urlsNote)); //filter out none URLs
        foreach ($urls as $url) {
            $endpoint = $this->discoverWebmentionEndpoint($url);
            if ($endpoint) {
                $this->guzzle->post($endpoint, [
                    'form_params' => [
                        'source' => $this->note->longurl,
                        'target' => $url
                    ]
                ])
            }
        }
    }

    /**
     * Discover if a URL has a webmention endpoint.
     *
     * @param  string  The URL
     * @param  \GuzzleHttp\Client $client
     * @return string  The webmention endpoint URL
     */
    private function discoverWebmentionEndpoint($url)
    {
        $endpoint = null;

        $response = $this->guzzle->get($url);
        //check HTTP Headers for webmention endpoint
        $links = \GuzzleHttp\Psr7\parse_header($response->getHeader('Link'));
        foreach ($links as $link) {
            if ($link['rel'] == 'webmention') {
                return trim($link[0], '<>');
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
            if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
                return $endpoint;
            }
            //it must be a relative url, so resolve with php-mf2
            return $mf2->resolveUrl($endpoint);
        }

        return false;
    }

    /**
     * Get the URLs from a note.
     */
    private function getLinks($html)
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
}
