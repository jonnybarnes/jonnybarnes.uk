<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\InvalidMentionException;
use App\Exceptions\RemoteContentNotFoundException;
use App\Models\Note;
use App\Models\WebMention;
use App\Services\WebMention\Authorship;
use App\Services\WebMention\Parser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Mf2;

class ProcessWebMention implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Note $note,
        protected string $source
    ) {}

    /**
     * Execute the job.
     *
     * @throws RemoteContentNotFoundException
     * @throws InvalidMentionException
     */
    public function handle(Parser $parser, Authorship $authorship): void
    {
        try {
            $response = Http::get($this->source);
        } catch (ConnectionException) {
            throw new RemoteContentNotFoundException;
        }
        if ($response->failed()) {
            throw new RemoteContentNotFoundException;
        }
        $this->saveRemoteContent($response->body(), $this->source);
        $microformats = Mf2\parse($response->body(), $this->source);
        $author = $authorship->resolve($microformats);
        $existing = WebMention::where('source', $this->source)
            ->whereMorphedTo('commentable', $this->note)
            ->first();
        if ($existing !== null) {
            // A re-sent mention: drop it if the source no longer references
            // this note, otherwise refresh the stored content
            if (! $this->stillReferencesNote($parser, $existing->type, $microformats)) {
                $existing->delete();

                return;
            }
            dispatch(new SaveProfileImage($author));
            $existing->mf2 = json_encode($microformats);
            $existing->author = $author;
            $existing->save();

            return;
        }

        // no webmention in the db so create new one
        $webmention = new WebMention;
        $type = $parser->getMentionType($microformats); // throw error here?
        dispatch(new SaveProfileImage($author));
        $webmention->source = $this->source;
        $webmention->target = $this->note->uri;
        $webmention->commentable_id = $this->note->id;
        $webmention->commentable_type = Note::class;
        $webmention->type = $type;
        $webmention->mf2 = json_encode($microformats);
        $webmention->author = $author;
        $webmention->save();
        dispatch(new NotifyBrrrOfWebMention($webmention));
    }

    /**
     * Check the source still makes the same kind of mention of this note.
     */
    private function stillReferencesNote(Parser $parser, ?string $type, array $microformats): bool
    {
        return match ($type) {
            'in-reply-to' => $parser->checkInReplyTo($microformats, $this->note->uri),
            'like-of' => $parser->checkLikeOf($microformats, $this->note->uri),
            'repost-of' => $parser->checkRepostOf($microformats, $this->note->uri),
            default => false,
        };
    }

    /**
     * Save the HTML of a webmention for future use.
     */
    private function saveRemoteContent(string $html, string $url): void
    {
        $filenameFromURL = str_replace(
            ['https://', 'http://'],
            ['https/', 'http/'],
            $url
        );
        if (str_ends_with($url, '/')) {
            $filenameFromURL .= 'index.html';
        }
        $path = storage_path().'/HTML/'.$filenameFromURL;
        $parts = explode('/', $path);
        $name = array_pop($parts);
        $dir = implode('/', $parts);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        file_put_contents("$dir/$name", $html);
    }
}
