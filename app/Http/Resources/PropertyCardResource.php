<?php

namespace App\Http\Resources;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public property-card shape, shared by the listing page and the
 * favourites lookup endpoint (PLAN.md §5).
 *
 * @mixin Property
 */
class PropertyCardResource extends JsonResource
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
            'title' => $this->getTranslation('title', app()->getLocale()),
            'listing_type' => $this->listing_type->value,
            'category' => $this->category->value,
            'is_exclusive' => $this->is_exclusive,
            'price' => $this->price,
            'price_negotiable' => $this->price_negotiable,
            'surface_m2' => $this->surface_m2,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'location' => $this->location?->getTranslation('name', app()->getLocale()),
            'municipality' => $this->location?->parent?->getTranslation('name', app()->getLocale()),
            'image_url' => $this->primaryImage?->url,
            'thumbnail_url' => $this->primaryImage?->thumbnail_url,
        ];
    }
}
