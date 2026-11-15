<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebMention;
use Illuminate\Console\Command;

class RemoveDuplicateWebMentions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webmentions:remove-duplicates
                            {--dry-run : Report the duplicates without deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate webmentions, keeping the oldest for each source and note';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $removed = 0;

        $groups = WebMention::query()
            ->select('source', 'commentable_type', 'commentable_id')
            ->selectRaw('MIN(id) AS keep_id')
            ->whereNotNull('commentable_id')
            ->groupBy('source', 'commentable_type', 'commentable_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $duplicates = WebMention::where('source', $group->source)
                ->where('commentable_type', $group->commentable_type)
                ->where('commentable_id', $group->commentable_id)
                ->where('id', '!=', $group->keep_id);

            $count = $duplicates->count();
            $removed += $count;
            $this->line("{$group->source}: {$count} duplicate(s) of webmention {$group->keep_id}");

            if (! $dryRun) {
                $duplicates->delete();
            }
        }

        $this->info($dryRun
            ? "Found {$removed} duplicate webmentions (dry run, nothing deleted)."
            : "Removed {$removed} duplicate webmentions.");
    }
}
