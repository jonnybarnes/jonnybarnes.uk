<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessWebMention;
use App\Models\Note;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Jonnybarnes\IndieWeb\Numbers;

class WebMentionsController extends Controller
{
    /**
     * Response to a GET request to the webmention endpoint.
     *
     * This is probably someone looking for information about what
     * webmentions are, or about my particular implementation.
     */
    public function get(): View
    {
        return view('webmention-endpoint');
    }

    /**
     * Receive and process a webmention.
     */
    public function receive(): Response
    {
        //first we trivially reject requests that lack all required inputs
        if ((request()->has('target') !== true) || (request()->has('source') !== true)) {
            return response(
                'You need both the target and source parameters',
                400
            );
        }

        //next check the $target is valid
        $path = parse_url(request()->input('target'), PHP_URL_PATH);
        $pathParts = explode('/', $path);

        if ($pathParts[1] == 'notes') {
            //we have a note
            $noteId = $pathParts[2];
            try {
                $note = Note::findOrFail(resolve(Numbers::class)->b60tonum($noteId));
                dispatch(new ProcessWebMention($note, request()->input('source')));
            } catch (ModelNotFoundException $e) {
                return response('This note doesn’t exist.', 400);
            }

            return response(
                'Webmention received, it will be processed shortly',
                202
            );
        }
        if ($pathParts[1] == 'blog') {
            return response(
                'I don’t accept webmentions for blog posts yet.',
                501
            );
        }

        return response(
            'Invalid request',
            400
        );
    }
}
