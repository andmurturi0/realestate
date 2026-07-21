<?php

namespace App\Http\Controllers\Site;

use App\Enums\PropertyStatus;
use App\Filters\PropertyFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyCardResource;
use App\Http\Resources\PropertyDetailResource;
use App\Models\Property;
use App\Services\FacetOptionsService;
use App\Services\PropertyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
        private readonly FacetOptionsService $facetOptionsService,
    ) {}

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
            'locations' => fn (): array => $this->facetOptionsService->municipalities(app()->getLocale()),
            'furnishingOptions' => fn (): array => $this->facetOptionsService->furnishingFeatures(app()->getLocale()),
        ]);
    }

    public function show(Request $request, Property $property): Response
    {
        // A non-published property is only visible via its public URL to the
        // owning agent or an admin (PropertyPolicy::viewPublic) — every other
        // case, including guests and other agents, gets 404 (FAZAT.md 6A).
        if ($property->status !== PropertyStatus::Published) {
            $user = $request->user();

            if ($user === null || Gate::forUser($user)->denies('viewPublic', $property)) {
                abort(404);
            }
        }

        $property->load([
            'agent:id,name,phone,whatsapp,avatar_path',
            'location:id,parent_id,name,type',
            'location.parent:id,name',
            'images',
            'features',
            'priceHistories',
        ]);

        $this->propertyService->registerView($property, $request);

        $similarProperties = $this->propertyService->similarTo($property)
            ->map(fn (Property $similar): array => (new PropertyCardResource($similar))->resolve())
            ->values();

        return Inertia::render('properties/Show', [
            'property' => (new PropertyDetailResource($property))->resolve(),
            'similarProperties' => $similarProperties,
        ]);
    }
}
