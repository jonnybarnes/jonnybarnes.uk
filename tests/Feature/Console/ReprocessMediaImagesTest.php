<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReprocessMediaImagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dry_run_lists_images_without_modifying_files(): void
    {
        Storage::disk('public')->put('media/test-image.jpg', file_get_contents(__DIR__.'/../../test-image.jpg'));

        Media::factory()->create([
            'path' => 'media/test-image.jpg',
            'image_widths' => 4592,
        ]);

        $this->artisan('app:reprocess-media-images', ['--dry-run' => true])
            ->expectsOutputToContain('Found 1 images to reprocess. (dry run)')
            ->expectsOutputToContain('media/test-image.jpg (4592px wide)')
            ->assertSuccessful();

        Storage::disk('public')->assertMissing('media/test-image-medium.jpg');
        Storage::disk('public')->assertMissing('media/test-image-small.jpg');

        Storage::disk('public')->delete('media/test-image.jpg');
    }

    #[Test]
    public function missing_original_file_is_skipped(): void
    {
        Media::factory()->create([
            'path' => 'media/does-not-exist.jpg',
            'image_widths' => 2000,
        ]);

        $this->artisan('app:reprocess-media-images')
            ->expectsOutputToContain('media/does-not-exist.jpg: original not found on public disk, skipping.')
            ->assertSuccessful();
    }

    #[Test]
    public function non_decodable_image_is_skipped(): void
    {
        Storage::disk('public')->put('media/not-an-image.jpg', 'This is not an image');

        Media::factory()->create([
            'path' => 'media/not-an-image.jpg',
            'image_widths' => 2000,
        ]);

        $this->artisan('app:reprocess-media-images')
            ->expectsOutputToContain('Could not decode image, skipping.')
            ->assertSuccessful();

        Storage::disk('public')->assertMissing('media/not-an-image-medium.jpg');
        Storage::disk('public')->assertMissing('media/not-an-image-small.jpg');

        Storage::disk('public')->delete('media/not-an-image.jpg');
    }

    #[Test]
    public function large_images_are_rescaled_into_medium_and_small_variants(): void
    {
        Storage::disk('public')->put('media/test-image.jpg', file_get_contents(__DIR__.'/../../test-image.jpg'));

        Media::factory()->create([
            'path' => 'media/test-image.jpg',
            'image_widths' => 4592,
        ]);

        $this->artisan('app:reprocess-media-images')
            ->expectsOutputToContain('Processing: media/test-image.jpg')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('media/test-image-medium.jpg');
        Storage::disk('public')->assertExists('media/test-image-small.jpg');

        Storage::disk('public')->delete('media/test-image.jpg');
        Storage::disk('public')->delete('media/test-image-medium.jpg');
        Storage::disk('public')->delete('media/test-image-small.jpg');
    }

    #[Test]
    public function images_with_width_at_or_below_threshold_are_not_processed(): void
    {
        Media::factory()->create([
            'path' => 'media/small-image.jpg',
            'image_widths' => 1000,
        ]);

        $this->artisan('app:reprocess-media-images')
            ->expectsOutputToContain('Found 0 images to reprocess.')
            ->assertSuccessful();
    }
}
