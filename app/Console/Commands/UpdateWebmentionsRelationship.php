<?php

namespace App\Console\Commands;

use App\Models\Note;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateWebmentionsRelationship extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webmentions:update-model-relationship';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update webmentions to relate to the correct note model class';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::table('webmentions')
            ->where('commentable_type', '=', 'App\Model\Note')
            ->update(['commentable_type' => Note::class]);

        $this->info('All webmentions updated to relate to the correct note model class');
    }
}
