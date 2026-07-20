<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Setting;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class ImageService
{
    /**
     * Session key holding images uploaded on the create form before the
     * property exists. Entries: {id: uuid, path, thumbnail_path}.
     */
    public const PENDING_SESSION_KEY = 'property_images.pending';

    public const MAX_PER_PROPERTY = 30;

    private const MAX_WIDTH = 1920;

    private const THUMBNAIL_WIDTH = 400;

    private const WEBP_QUALITY = 85;

    private const PENDING_DIRECTORY = 'tmp/pending-uploads';

    /**
     * S3 metadata for every stored image. Without an explicit ContentType
     * Supabase serves the files as application/octet-stream and Chrome's
     * ORB refuses to render them.
     *
     * @var array<string, string>
     */
    public const STORAGE_OPTIONS = ['ContentType' => 'image/webp', 'visibility' => 'public'];

    /**
     * Process an upload and attach it to the property. The first image of
     * a property becomes primary automatically (model event).
     */
    public function store(Property $property, UploadedFile $file): PropertyImage
    {
        [$path, $thumbnailPath] = $this->processAndPut($file, "properties/{$property->id}");

        return $property->images()->create([
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'sort_order' => ((int) $property->images()->max('sort_order')) + 1,
        ]);
    }

    /**
     * Process an upload for a property that does not exist yet: files land
     * in a temp directory and are remembered in the session until
     * attachPending() moves them under the created property.
     *
     * @return array{id: string, path: string, thumbnail_path: string}
     */
    public function storePending(UploadedFile $file): array
    {
        [$path, $thumbnailPath] = $this->processAndPut($file, self::PENDING_DIRECTORY);

        $entry = [
            'id' => Str::before(basename($path), '.webp'),
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
        ];

        session()->push(self::PENDING_SESSION_KEY, $entry);

        return $entry;
    }

    public function destroyPending(string $id): void
    {
        $entries = collect(session(self::PENDING_SESSION_KEY, []));

        $entry = $entries->firstWhere('id', $id);

        if ($entry === null) {
            return;
        }

        $this->disk()->delete([$entry['path'], $entry['thumbnail_path']]);

        session()->put(self::PENDING_SESSION_KEY, $entries->reject(
            fn (array $candidate): bool => $candidate['id'] === $id
        )->values()->all());
    }

    /**
     * Move the session's pending uploads under the freshly created
     * property, in upload order, and clear the session key.
     */
    public function attachPending(Property $property): void
    {
        foreach (session(self::PENDING_SESSION_KEY, []) as $entry) {
            $directory = "properties/{$property->id}";
            $path = "{$directory}/".basename($entry['path']);
            $thumbnailPath = "{$directory}/".basename($entry['thumbnail_path']);

            // Not move(): flysystem's S3 copy reads the source ACL first,
            // which Supabase's S3 API does not support. A fresh put also
            // re-stamps the ContentType metadata.
            $this->moveByRewrite($entry['path'], $path);
            $this->moveByRewrite($entry['thumbnail_path'], $thumbnailPath);

            $property->images()->create([
                'path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'sort_order' => ((int) $property->images()->max('sort_order')) + 1,
            ]);
        }

        session()->forget(self::PENDING_SESSION_KEY);
    }

    /**
     * Remove the image from disk and database; if it was primary the model
     * event promotes the next image in sort order.
     */
    public function delete(PropertyImage $image): void
    {
        foreach ([$image->path, $image->thumbnail_path] as $path) {
            if ($path !== null && ! Str::startsWith($path, ['http://', 'https://'])) {
                $this->disk()->delete($path);
            }
        }

        $image->delete();
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(Property $property, array $orderedIds): void
    {
        DB::transaction(function () use ($property, $orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                $property->images()->whereKey($id)->update(['sort_order' => $index]);
            }
        });
    }

    public function makePrimary(PropertyImage $image): void
    {
        $image->update(['is_primary' => true]);
    }

    /**
     * Resize to max 1920px wide as webp q85 (watermarked when enabled),
     * plus a 400px thumbnail (never watermarked), stored side by side.
     *
     * @return array{0: string, 1: string} [path, thumbnail_path]
     */
    private function processAndPut(UploadedFile $file, string $directory): array
    {
        $manager = ImageManager::gd();
        $uuid = (string) Str::uuid();

        $large = $manager->read($file->getRealPath())->scaleDown(width: self::MAX_WIDTH);
        $this->applyWatermark($large, $manager);

        $thumbnail = $manager->read($file->getRealPath())->scaleDown(width: self::THUMBNAIL_WIDTH);

        $path = "{$directory}/{$uuid}.webp";
        $thumbnailPath = "{$directory}/{$uuid}_thumb.webp";

        $this->disk()->put($path, (string) $large->toWebp(self::WEBP_QUALITY), self::STORAGE_OPTIONS);
        $this->disk()->put($thumbnailPath, (string) $thumbnail->toWebp(self::WEBP_QUALITY), self::STORAGE_OPTIONS);

        return [$path, $thumbnailPath];
    }

    /**
     * Composite the agency logo bottom-right at 40% opacity when
     * settings.watermark_enabled is on and a logo has been uploaded.
     */
    private function applyWatermark(ImageInterface $image, ImageManager $manager): void
    {
        if (! Setting::get('watermark_enabled')) {
            return;
        }

        $logoPath = Setting::get('logo_path');

        if (! is_string($logoPath) || $logoPath === '' || ! $this->disk()->exists($logoPath)) {
            return;
        }

        $logo = $manager->read($this->disk()->get($logoPath))
            ->scaleDown(width: max(1, intdiv($image->width(), 4)));

        $image->place($logo, 'bottom-right', 24, 24, 40);
    }

    private function moveByRewrite(string $from, string $to): void
    {
        $this->disk()->put($to, $this->disk()->get($from), self::STORAGE_OPTIONS);
        $this->disk()->delete($from);
    }

    private function disk(): Filesystem
    {
        return Storage::disk('supabase');
    }
}
