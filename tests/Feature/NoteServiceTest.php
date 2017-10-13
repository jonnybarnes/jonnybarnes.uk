<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\NoteService;
use App\Jobs\SyndicateNoteToTwitter;
use App\Jobs\SyndicateNoteToFacebook;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NoteServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_syndicate_to_twitter_job_is_sent()
    {
        Queue::fake();

        $noteService = new NoteService();
        $note = $noteService->createNote([
            'content' => 'Hello Fred',
            'in-reply-to' => 'https://fredbloggs.com/note/abc',
            'syndicate' => ['twitter'],
        ]);

        Queue::assertPushed(SyndicateNoteToTwitter::class);
    }

    public function test_syndicate_to_facebook_job_is_sent()
    {
        Queue::fake();

        $noteService = new NoteService();
        $note = $noteService->createNote([
            'content' => 'Hello Fred',
            'in-reply-to' => 'https://fredbloggs.com/note/abc',
            'syndicate' => ['facebook'],
        ]);

        Queue::assertPushed(SyndicateNoteToFacebook::class);
    }

    public function test_syndicate_to_target_jobs_are_sent()
    {
        Queue::fake();

        $noteService = new NoteService();
        $note = $noteService->createNote([
            'content' => 'Hello Fred',
            'in-reply-to' => 'https://fredbloggs.com/note/abc',
            'syndicate' => ['twitter', 'facebook'],
        ]);

        Queue::assertPushed(SyndicateNoteToTwitter::class);
        Queue::assertPushed(SyndicateNoteToFacebook::class);
    }
}
