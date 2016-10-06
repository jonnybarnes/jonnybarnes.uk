<?php

namespace App\Jobs;

use Twitter;
use App\Note;
use App\Place;
use App\Contact;
use Illuminate\Bus\Queueable;
use Jonnybarnes\IndieWeb\Numbers;
use Jonnybarnes\IndieWeb\NotePrep;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyndicateToTwitter implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $note;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    /**
     * Execute the job.
     *
     * @param  \Jonnybarnes\IndieWeb\Numbers $numbers
     * @param  \Jonnybarnes\IndieWeb\NotePrep $noteprep
     * @return void
     */
    public function handle(Numbers $numbers, NotePrep $noteprep)
    {
        $noteSwappedNames = $this->swapNames($this->note->getOriginal('note'));
        $shorturl = 'https://' . config('url.shorturl') . '/t/' . $numbers->numto60($this->note->id);
        $tweet = $noteprep->createNote($noteSwappedNames, $shorturl, 140, true);
        $tweetOpts = ['status' => $tweet, 'format' => 'json'];
        if ($this->note->in_reply_to) {
            $tweetOpts['in_reply_to_status_id'] = $noteprep->replyTweetId($this->note->in_reply_to);
        }

        if ($this->note->location) {
            $explode = explode(':', $this->note->location);
            $location = (count($explode) == 2) ? explode(',', $explode[0]) : explode(',', $explode);
            $lat = trim($location[0]);
            $lng = trim($location[1]);
        }
        if ($this->note->place_id) {
            //we force the job to create a place model to get access
            //to the postgis methods
            $place = Place::find($this->note->place_id)
            $lat = $place->location->getLat();
            $lng = $place->location->getLng();
        }
        if (isset($lat) && isset($lng)) {
            $tweetOpts['lat'] = $lat;
            $tweetOpts['long'] = $lng;
        }

        $mediaItems = $this->note->getMedia();
        if (count($mediaItems) > 0) {
            foreach ($mediaItems as $item) {
                $uploadedMedia = Twitter::uploadMedia(['media' => file_get_contents($item->getUrl())]);
                $mediaIds[] = $uploadedMedia->media_id_string;
            }
            $tweetOpts['media_ids'] = implode(',', $mediaIds);
        }

        $responseJson = Twitter::postTweet($tweetOpts);
        $response = json_decode($responseJson);
        $this->note->tweet_id = $response->id;
        $this->note->save();
    }

    /**
     * Swap @names in a note.
     *
     * When a note is being saved and we are posting it to twitter, we want
     * to swap our @local_name to Twitter’s @twitter_name so the user get’s
     * mentioned on Twitter.
     *
     * @param  string $note
     * @return string $noteSwappedNames
     */
    private function swapNames($note)
    {
        $regex = '/\[.*?\](*SKIP)(*F)|@(\w+)/'; //match @alice but not [@bob](...)
        $noteSwappedNames = preg_replace_callback(
            $regex,
            function ($matches) {
                try {
                    $contact = Contact::where('nick', '=', mb_strtolower($matches[1]))->firstOrFail();
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    return '@' . $matches[1];
                }
                $twitterHandle = $contact->twitter;

                return '@' . $twitterHandle;
            },
            $note
        );

        return $noteSwappedNames;
    }
}
