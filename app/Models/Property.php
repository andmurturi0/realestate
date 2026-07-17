<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\DocumentType;
use App\Enums\ListingType;
use App\Enums\PropertyCategory;
use App\Enums\PropertyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Property extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'description', 'meta_title', 'meta_description'];

    protected $fillable = [
        'agent_id',
        'title',
        'slug',
        'description',
        'listing_type',
        'is_exclusive',
        'category',
        'price',
        'price_negotiable',
        'surface_m2',
        'land_surface_m2',
        'bedrooms',
        'bathrooms',
        'floor',
        'total_floors',
        'year_built',
        'parking_spaces',
        'location_id',
        'address_line',
        'lat',
        'lng',
        'has_possession_sheet',
        'document_type',
        'status',
        'published_at',
        'views_count',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    protected $attributes = [
        'is_exclusive' => false,
        'price_negotiable' => false,
        'is_featured' => false,
        'status' => 'draft',
        'views_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'listing_type' => ListingType::class,
            'is_exclusive' => 'boolean',
            'category' => PropertyCategory::class,
            'price' => MoneyCast::class,
            'price_negotiable' => 'boolean',
            'surface_m2' => 'float',
            'land_surface_m2' => 'float',
            'lat' => 'float',
            'lng' => 'float',
            'has_possession_sheet' => 'boolean',
            'document_type' => DocumentType::class,
            'status' => PropertyStatus::class,
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Property $property): void {
            if (blank($property->slug)) {
                $property->slug = static::generateUniqueSlug(
                    $property->getTranslation('title', 'sq', false)
                );
            }
        });

        static::created(function (Property $property): void {
            $property->reference_code = 'PRO-'.str_pad((string) $property->id, 4, '0', STR_PAD_LEFT);
            $property->saveQuietly();
        });

        static::updating(function (Property $property): void {
            if ($property->isDirty('price')) {
                PropertyPriceHistory::create([
                    'property_id' => $property->id,
                    'old_price' => $property->getOriginal('price'),
                    'new_price' => $property->price,
                    'changed_by' => auth()->id(),
                ]);
            }
        });
    }

    protected static function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'prona';
        $slug = $base;
        $suffix = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<PropertyImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    /**
     * @return HasOne<PropertyImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(PropertyImage::class)->where('is_primary', true);
    }

    /**
     * @return HasMany<PropertyPriceHistory, $this>
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(PropertyPriceHistory::class)->orderBy('created_at');
    }

    /**
     * @return BelongsToMany<Feature, $this>
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class);
    }

    /**
     * @return HasMany<ContactMessage, $this>
     */
    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }
}
