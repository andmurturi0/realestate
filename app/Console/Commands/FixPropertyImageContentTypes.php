<?php

namespace App\Console\Commands;

use App\Models\PropertyImage;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One-off repair (FAZA 4B follow-up): images uploaded before ImageService
 * set an explicit ContentType are stored as application/octet-stream in
 * Supabase, so browsers refuse to render them (ORB). Re-putting each file
 * with the correct metadata fixes the stored objects in place.
 */
class FixPropertyImageContentTypes extends Command
{
    protected $signature = 'properties:fix-image-content-types';

    protected $description = 'Re-put every stored property image with image/webp ContentType metadata';

    public function handle(): int
    {
        $disk = Storage::disk('supabase');
        $fixed = 0;
        $skipped = 0;
        $missing = 0;

        PropertyImage::query()->each(function (PropertyImage $image) use ($disk, &$fixed, &$skipped, &$missing): void {
            foreach ([$image->path, $image->thumbnail_path] as $path) {
                if ($path === null || Str::startsWith($path, ['http://', 'https://'])) {
                    $skipped++;

                    continue;
                }

                if (! Str::endsWith($path, '.webp')) {
                    $this->warn("Skipped (not webp): {$path}");
                    $skipped++;

                    continue;
                }

                if (! $disk->exists($path)) {
                    $this->warn("Missing on disk: {$path}");
                    $missing++;

                    continue;
                }

                $disk->put($path, $disk->get($path), ImageService::STORAGE_OPTIONS);
                $fixed++;
            }
        });

        $this->info("Fixed: {$fixed}, skipped: {$skipped}, missing on disk: {$missing}.");

        return self::SUCCESS;
    }
}
