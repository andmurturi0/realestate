<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    /**
     * Persist a set of settings key/value pairs. The settings cache is
     * invalidated automatically through the Setting model's saved event.
     *
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /**
     * Resolve the *_path settings (stored relative to the supabase disk) into
     * public *_url keys the frontend can render directly.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function withImageUrls(array $settings): array
    {
        $imageKeys = [
            'logo_path' => 'logo_url',
            'logo_dark_path' => 'logo_dark_url',
            'favicon_path' => 'favicon_url',
        ];

        foreach ($imageKeys as $pathKey => $urlKey) {
            $settings[$urlKey] = empty($settings[$pathKey])
                ? null
                : Storage::disk('supabase')->url($settings[$pathKey]);
        }

        return $settings;
    }

    /**
     * Store an uploaded branding image on the supabase disk under branding/,
     * save its path under the given settings key, and delete the file it replaces.
     */
    public function storeBrandingImage(string $key, UploadedFile $file): void
    {
        $previous = Setting::get($key);

        // Explicit ContentType: without it Supabase serves the file as
        // application/octet-stream and browsers (ORB) refuse to render it.
        $path = $file->storeAs('branding', $file->hashName(), [
            'disk' => 'supabase',
            'ContentType' => $file->getMimeType(),
            'visibility' => 'public',
        ]);

        $this->update([$key => $path]);

        if (is_string($previous) && $previous !== '' && Storage::disk('supabase')->exists($previous)) {
            Storage::disk('supabase')->delete($previous);
        }
    }
}
