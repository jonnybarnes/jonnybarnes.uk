<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\NotifyBrrrOfWebMention;
use App\Models\WebMention;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotifyBrrrOfWebMentionJobTest extends TestCase
{
    #[Test]
    public function it_posts_a_reply_notification_to_the_brrr_webhook(): void
    {
        config(['services.brrr.webhook_url' => 'https://api.brrr.now/v1/br_usr_test']);
        Http::fake();

        $webMention = WebMention::factory()->make([
            'source' => 'https://example.org/reply/1',
            'target' => 'https://jonnybarnes.uk/notes/1',
            'type' => 'in-reply-to',
        ]);

        $job = new NotifyBrrrOfWebMention($webMention);
        $job->handle();

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.brrr.now/v1/br_usr_test'
                && $request['title'] === 'New reply'
                && $request['message'] === 'From https://example.org/reply/1'
                && $request['open_url'] === 'https://jonnybarnes.uk/notes/1';
        });
    }

    #[Test]
    public function it_titles_notifications_by_webmention_type(): void
    {
        config(['services.brrr.webhook_url' => 'https://api.brrr.now/v1/br_usr_test']);
        Http::fake();

        foreach ([
            'in-reply-to' => 'New reply',
            'like-of' => 'New like',
            'repost-of' => 'New repost',
            'something-else' => 'New webmention',
        ] as $type => $expectedTitle) {
            $webMention = WebMention::factory()->make(['type' => $type]);

            (new NotifyBrrrOfWebMention($webMention))->handle();

            Http::assertSent(fn (Request $request) => $request['title'] === $expectedTitle);
        }
    }

    #[Test]
    public function it_does_nothing_when_no_webhook_url_is_configured(): void
    {
        config(['services.brrr.webhook_url' => null]);
        Http::fake();

        $webMention = WebMention::factory()->make();

        (new NotifyBrrrOfWebMention($webMention))->handle();

        Http::assertNothingSent();
    }
}
