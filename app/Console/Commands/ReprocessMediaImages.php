<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Image\ImageException;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ReprocessMediaImages extends Command
{
    protected $signature = 'app:reprocess-media-images {--dry-run : List images that would be processed without making changes}';

    protected $description = 'Regenerate medium and small image variants using correct aspect-ratio scaling';

    public function handle(): void
    {
        $media = Media::where('type', 'image')
            ->whereNotNull('image_widths')
            ->where('image_widths', '>', 1000)
            ->get();

        $dryRun = $this->option('dry-run');

        $this->info("Found {$media->count()} images to reprocess.".($dryRun ? ' (dry run)' : ''));

        foreach ($media as $item) {
            $path = $item->path;

            if (! Storage::disk('public')->exists($path)) {
                $this->warn("{$path}: original not found on public disk, skipping.");

                continue;
            }

            if ($dryRun) {
                $this->line("{$path} ({$item->image_widths}px wide)");

                continue;
            }

            $this->info("Processing: {$path}");

            $image = Image::fromStorage($path, 'public');
            try {
                $image->width();
            } catch (ImageException) {
                $this->warn('  Could not decode image, skipping.');

                continue;
            }

            $filenameParts = explode('.', $path);
            $extension = array_pop($filenameParts);
            $basename = trim(implode('.', $filenameParts), '.');

            Storage::disk('public')->put($basename.'-medium.'.$extension, $image->scale(width: 1000)->toBytes());
            Storage::disk('public')->put($basename.'-small.'.$extension, $image->scale(width: 500)->toBytes());

            $this->info('  Done.');
        }

        $this->info('Reprocessing complete.'.($dryRun ? ' (dry run — no files were changed)' : ''));
    }
}
