<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebMention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class NotifyBrrrOfWebMention implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected WebMention $webMention
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('services.brrr.webhook_url');

        if (blank($webhookUrl)) {
            return;
        }

        Http::post($webhookUrl, [
            'title' => $this->title(),
            'message' => "From {$this->webMention->source}",
            'open_url' => $this->webMention->target,
        ]);
    }

    /**
     * Build a notification title based on the webmention type.
     */
    private function title(): string
    {
        return match ($this->webMention->type) {
            'in-reply-to' => 'New reply',
            'like-of' => 'New like',
            'repost-of' => 'New repost',
            default => 'New webmention',
        };
    }
}
