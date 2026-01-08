<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Note;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-media';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate media';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // First check new `media_note` table exists
        if (! DB::getSchemaBuilder()->hasTable('media_note')) {
            $this->error('The table "media_note" does not exist.');

            exit(1);
        }

        // Load all media already saved in `media_endpoint` table
        $this->line('Updating existing local media');
        $mediaEndpointMedia = Media::all();
        // Save relationship in new `media_note` table based on `media_endpoint.note_id`
        $this->withProgressBar($mediaEndpointMedia, function (Media $mediaEndpointMediaItem) {
            $note = Note::find($mediaEndpointMediaItem->note_id);
            if ($note) {
                $note->media()->attach($mediaEndpointMediaItem->id);
            }
        });

        // Load all media records from `media` table
        $this->line('');
        $this->line('Migrating old media from S3');
        $oldMedia = DB::table('media')->get();
        foreach ($oldMedia as $oldMediaItem) {
            // We only want to process the S3 media
            if ($oldMediaItem->disk !== 's3') {
                $this->warn('Original media item never stored in S3');

                continue;
            }

            // Check media exists in S3
            if (! Storage::disk('s3')->exists($oldMediaItem->id.DIRECTORY_SEPARATOR.$oldMediaItem->file_name)) {
                $this->warn('Original media item not found in S3');

                continue;
            }
            // We want to just copy the file, check it does not already exist locally
            if (Storage::disk('public')->exists('media'.DIRECTORY_SEPARATOR.$oldMediaItem->file_name)) {
                $this->warn('File already exists locally with filename of original media item');

                continue;
            }

            // Save relationship based on `media.model_id`
            // I have already checked they are all notes
            $noteId = $oldMediaItem->model_id;
            $note = Note::find($noteId);
            if (! $note) {
                $this->warn('Note no longer exists');

                continue;
            }

            // Create media entry in database and attach to note
            $newMediaItem = Media::create([
                'path' => 'media'.DIRECTORY_SEPARATOR.$oldMediaItem->file_name,
                'type' => 'image',
            ]);
            $note->media()->attach($newMediaItem->id);

            // Copy the file
            Storage::disk('public')->writeStream('media'.DIRECTORY_SEPARATOR.$oldMediaItem->file_name, Storage::disk('s3')->readStream($oldMediaItem->id.DIRECTORY_SEPARATOR.$oldMediaItem->file_name));

            $this->info('Media item migrated from S3');
        }

        $this->line('');
        $this->line('Migration finished');
    }
}
