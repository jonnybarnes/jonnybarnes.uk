<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use App\Exceptions\MicropubUnsupportedModelException;
use App\Models\Note;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Finds the note a Micropub request refers to by its URL.
 */
class NoteResolver
{
    /**
     * @throws MicropubUnsupportedModelException if the URL is not a note on this site
     * @throws ModelNotFoundException if no such note exists
     */
    public function fromUrl(string $url): Note
    {
        $host = parse_url($url, PHP_URL_HOST);
        $ourHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($host) || ! is_string($ourHost) || strcasecmp($host, $ourHost) !== 0) {
            throw new MicropubUnsupportedModelException('The URL is not a post on this site');
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        if (preg_match('#^/notes/([0-9A-Za-z_]+)/?$#', $path, $matches) !== 1) {
            throw new MicropubUnsupportedModelException('This implementation currently only supports notes');
        }

        try {
            return Note::nb60($matches[1])->firstOrFail();
        } catch (NotFoundHttpException) {
            // Note::scopeNb60() aborts when the ID is too big for the database
            throw (new ModelNotFoundException)->setModel(Note::class);
        }
    }
}
