<?php

namespace App\Http\Controllers\Site;

use App\Enums\FeatureGroup;
use App\Enums\LocationType;
use App\Filters\PropertyFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyCardResource;
use App\Models\Feature;
use App\Models\Location;
use App\Models\Property;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = PropertyFilter::fromRequest($request);

        $properties = $filter
            ->apply(
                Property::query()
                    ->published()
                    ->select([
                        'id', 'slug', 'reference_code', 'agent_id', 'location_id',
                        'title', 'listing_type', 'category', 'is_exclusive',
                        'price', 'price_negotiable', 'surface_m2', 'bedrooms',
                        'bathrooms', 'published_at',
                    ])
                    ->with([
                        'agent:id,name,phone,whatsapp,avatar_path',
                        'location:id,parent_id,name,type',
                        'location.parent:id,name',
                        'primaryImage',
                    ])
            )
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Property $property): array => (new PropertyCardResource($property))->resolve());

        return Inertia::render('properties/Index', [
            'properties' => $properties,
            // Normalized filter state, partial-reloaded together with the
            // results so history entries (back button) stay consistent.
            'filters' => $filter->active(),
            // Facet options are closures: partial reloads skip them entirely.
            'locations' => fn (): array => $this->locationOptions(),
            'furnishingOptions' => fn (): array => $this->furnishingOptions(),
        ]);
    }

    /**
     * Municipalities with their neighborhoods, for the location dropdown.
     *
     * @return list<array<string, mixed>>
     */
    private function locationOptions(): array
    {
        return Location::query()
            ->where('type', LocationType::Municipality)
            ->with(['children' => fn ($query) => $query->orderBy('slug')])
            ->orderBy('slug')
            ->get()
            ->map(fn (Location $municipality): array => [
                'id' => $municipality->id,
                'name' => $municipality->getTranslation('name', 'sq'),
                'neighborhoods' => $municipality->children->map(fn (Location $neighborhood): array => [
                    'id' => $neighborhood->id,
                    'name' => $neighborhood->getTranslation('name', 'sq'),
                ])->all(),
            ])
            ->all();
    }

    /**
     * @return list<array{key: string, name: string}>
     */
    private function furnishingOptions(): array
    {
        return Feature::query()
            ->where('group', FeatureGroup::Furnishing)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Feature $feature): array => [
                'key' => $feature->key,
                'name' => $feature->getTranslation('name', 'sq'),
            ])
            ->all();
    }
}
