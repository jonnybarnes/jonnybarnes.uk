<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\NoteService;
use App\Jobs\SyndicateToTwitter;
use App\Jobs\SyndicateToFacebook;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
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

        Queue::assertPushed(SyndicateToTwitter::class);
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

        Queue::assertPushed(SyndicateToFacebook::class);
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

        Queue::assertPushed(SyndicateToTwitter::class);
        Queue::assertPushed(SyndicateToFacebook::class);
    }
}
