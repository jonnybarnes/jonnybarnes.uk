<?php

namespace App\Http\Controllers;

use App\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Jobs\SendWebMentions;
use App\Jobs\ProcessWebMention;
use Jonnybarnes\IndieWeb\Numbers;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WebMentionsController extends Controller
{
    /**
     * Receive and process a webmention.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Respone
     */
    public function receive(Request $request)
    {
        //first we trivially reject requets that lack all required inputs
        if (($request->has('target') !== true) || ($request->has('source') !== true)) {
            return new Response(
                'You need both the target and source parameters',
                400
            );
        }

        //next check the $target is valid
        $path = parse_url($request->input('target'))['path'];
        $pathParts = explode('/', $path);

        switch ($pathParts[1]) {
            case 'notes':
                //we have a note
                $noteId = $pathParts[2];
                $numbers = new Numbers();
                $realId = $numbers->b60tonum($noteId);
                try {
                    $note = Note::findOrFail($realId);
                    $this->dispatch(new ProcessWebMention($note, $request->input('source')));
                } catch (ModelNotFoundException $e) {
                    return new Response('This note doesn’t exist.', 400);
                }

                return new Response(
                    'Webmention received, it will be processed shortly',
                    202
                );
                break;
            case 'blog':
                return new Response(
                    'I don’t accept webmentions for blog posts yet.',
                    501
                );
                break;
            default:
                return new Response(
                    'Invalid request',
                    400
                );
                break;
        }
    }

    /**
     * Send a webmention.
     *
     * @param  \App\Note  $note
     * @return array   An array of successful then failed URLs
     */
    public function send(Note $note)
    {
        //grab the URLs
        $urlsInReplyTo = explode(' ', $note->in_reply_to);
        $urlsNote = $this->getLinks($note->note);
        $urls = array_filter(array_merge($urlsInReplyTo, $urlsNote)); //filter out none URLs
        foreach ($urls as $url) {
            $this->dispatch(new SendWebMentions($url, $note->longurl));
        }
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
