<?php

namespace App\Models;

use Database\Factories\PropertyImageFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class PropertyImage extends Model
{
    /** @use HasFactory<PropertyImageFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['alt_text'];

    protected $fillable = [
        'property_id',
        'path',
        'thumbnail_path',
        'is_primary',
        'sort_order',
        'alt_text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Exactly one primary image per property: the first image becomes
     * primary automatically, promoting one demotes its siblings, and
     * deleting the primary hands the flag to the next image in order.
     */
    protected static function booted(): void
    {
        static::creating(function (PropertyImage $image): void {
            if (! $image->is_primary) {
                $image->is_primary = ! static::query()
                    ->where('property_id', $image->property_id)
                    ->where('is_primary', true)
                    ->exists();
            }
        });

        static::saved(function (PropertyImage $image): void {
            if ($image->is_primary) {
                static::query()
                    ->where('property_id', $image->property_id)
                    ->whereKeyNot($image->getKey())
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });

        static::deleted(function (PropertyImage $image): void {
            if ($image->is_primary) {
                static::query()
                    ->where('property_id', $image->property_id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first()
                    ?->update(['is_primary' => true]);
            }
        });
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): ?string => static::publicUrl($this->path));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => static::publicUrl($this->thumbnail_path ?? $this->path));
    }

    /**
     * Absolute URLs (seeded placeholder images) pass through untouched;
     * everything else resolves against the supabase disk.
     */
    public static function publicUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : Storage::disk('supabase')->url($path);
    }
}
