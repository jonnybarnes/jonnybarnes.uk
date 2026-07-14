<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;

class ReprocessMediaImages extends Command
{
    protected $signature = 'app:reprocess-media-images {--dry-run : List images that would be processed without making changes}';

    protected $description = 'Regenerate medium and small image variants using correct aspect-ratio scaling';

    public function handle(ImageManager $manager): void
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

            try {
                $file = Storage::disk('public')->get($path);
                $image = $manager->read($file);
            } catch (DecoderException) {
                $this->warn('  Could not decode image, skipping.');

                continue;
            }

            $filenameParts = explode('.', $path);
            $extension = array_pop($filenameParts);
            $basename = trim(implode('.', $filenameParts), '.');

            $medium = $image->scale(width: 1000);
            Storage::disk('public')->put($basename.'-medium.'.$extension, (string) $medium->encode());

            $small = $image->scale(width: 500);
            Storage::disk('public')->put($basename.'-small.'.$extension, (string) $small->encode());

            $this->info('  Done.');
        }

        $this->info('Reprocessing complete.'.($dryRun ? ' (dry run — no files were changed)' : ''));
    }
}
