<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ListingType;
use App\Enums\OfferStatus;
use App\Enums\PropertyCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PropertyOffer extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyOfferFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'listing_type',
        'category',
        'location_id',
        'surface_m2',
        'asking_price',
        'status',
        'assigned_to',
        'converted_property_id',
        'ip_address',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'listing_type' => ListingType::class,
            'category' => PropertyCategory::class,
            'surface_m2' => 'float',
            'asking_price' => MoneyCast::class,
            'status' => OfferStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function convertedProperty(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'converted_property_id');
    }

    /**
     * @return MorphMany<LeadNote, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(LeadNote::class, 'notable')->latest('created_at');
    }
}
