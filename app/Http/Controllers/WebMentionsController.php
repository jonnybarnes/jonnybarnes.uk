<?php

namespace App\Http\Controllers;

use App\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Jobs\ProcessWebMention;
use Jonnybarnes\IndieWeb\Numbers;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WebMentionsController extends Controller
{
    public function get()
    {
        return view('webmention-endpoint');
    }

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
        $path = parse_url($request->input('target'), PHP_URL_PATH);
        $pathParts = explode('/', $path);

        if ($pathParts[1] == 'notes') {
            //we have a note
            $noteId = $pathParts[2];
            $numbers = new Numbers();
            try {
                $note = Note::findOrFail($numbers->b60tonum($noteId));
                dispatch(new ProcessWebMention($note, $request->input('source')));
            } catch (ModelNotFoundException $e) {
                return new Response('This note doesn’t exist.', 400);
            }

            return new Response(
                'Webmention received, it will be processed shortly',
                202
            );
        }
        if ($pathParts[1] == 'blog') {
            return new Response(
                'I don’t accept webmentions for blog posts yet.',
                501
            );
        }

        return new Response(
            'Invalid request',
            400
        );
    }
}
