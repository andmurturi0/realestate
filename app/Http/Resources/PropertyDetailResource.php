<?php

namespace App\Http\Resources;

use App\Models\Feature;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full public property-detail payload (FAZAT.md Faza 6A).
 *
 * @mixin Property
 */
class PropertyDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'reference_code' => $this->reference_code,
            'title' => $this->getTranslation('title', 'sq'),
            'description' => $this->getTranslation('description', 'sq'),
            'listing_type' => $this->listing_type->value,
            'category' => $this->category->value,
            'is_exclusive' => $this->is_exclusive,
            'price' => $this->price,
            'price_negotiable' => $this->price_negotiable,
            'surface_m2' => $this->surface_m2,
            'land_surface_m2' => $this->land_surface_m2,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'floor' => $this->floor,
            'total_floors' => $this->total_floors,
            'year_built' => $this->year_built,
            'parking_spaces' => $this->parking_spaces,
            'has_possession_sheet' => $this->has_possession_sheet,
            'document_type' => $this->document_type?->value,
            'published_at' => $this->published_at?->toIso8601String(),
            'views_count' => $this->views_count,
            'location' => $this->location === null ? null : [
                'name' => $this->location->getTranslation('name', 'sq'),
                'municipality' => $this->location->parent?->getTranslation('name', 'sq')
                    ?? $this->location->getTranslation('name', 'sq'),
                'lat' => $this->lat,
                'lng' => $this->lng,
            ],
            'agent' => $this->agent === null ? null : [
                'name' => $this->agent->name,
                'phone' => $this->agent->phone,
                'whatsapp' => $this->agent->whatsapp,
                'avatar_url' => $this->agent->avatar_path === null ? null : PropertyImage::publicUrl($this->agent->avatar_path),
            ],
            'images' => $this->images->map(fn (PropertyImage $image): array => [
                'id' => $image->id,
                'url' => $image->url,
                'thumbnail_url' => $image->thumbnail_url,
                'alt' => $image->getTranslation('alt_text', 'sq'),
                'is_primary' => $image->is_primary,
            ])->values(),
            'features' => $this->features
                ->groupBy(fn (Feature $feature): string => $feature->group->value)
                ->map(fn ($group) => $group
                    ->sortBy('sort_order')
                    ->map(fn (Feature $feature): array => [
                        'key' => $feature->key,
                        'name' => $feature->getTranslation('name', 'sq'),
                        'icon' => $feature->icon,
                    ])
                    ->values()),
            'price_history' => $this->priceHistories->count() >= 2
                ? $this->priceHistories->map(fn (PropertyPriceHistory $history): array => [
                    'date' => $history->created_at->toDateString(),
                    'price' => $history->new_price,
                ])->values()
                : [],
        ];
    }
}
