<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\LocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StorePropertyRequest;
use App\Http\Requests\Dashboard\UpdatePropertyRequest;
use App\Models\Feature;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use App\Services\ImageService;
use App\Services\PropertyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function __construct(private readonly PropertyService $propertyService) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Property::class);

        $user = $request->user();

        $filters = $request->only(['status', 'category', 'listing_type', 'agent', 'search', 'sort', 'direction']);

        $properties = $this->propertyService
            ->paginateForDashboard($user, $filters)
            ->through(fn (Property $property): array => [
                'id' => $property->id,
                'reference_code' => $property->reference_code,
                'title' => $property->getTranslation('title', 'sq'),
                'category' => $property->category->value,
                'listing_type' => $property->listing_type->value,
                'is_exclusive' => $property->is_exclusive,
                'price' => $property->price,
                'location' => $property->location?->getTranslation('name', 'sq'),
                'agent' => $property->agent?->name,
                'status' => $property->status->value,
                'views_count' => $property->views_count,
                'thumbnail_url' => $property->primaryImage?->thumbnail_url,
                'can' => [
                    'update' => $user->can('update', $property),
                    'delete' => $user->can('delete', $property),
                ],
            ]);

        return Inertia::render('dashboard/properties/Index', [
            'properties' => $properties,
            'filters' => $filters,
            'agents' => $this->agentOptions($user),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Property::class);

        return Inertia::render('dashboard/properties/Create', [
            'pendingImages' => collect($request->session()->get(ImageService::PENDING_SESSION_KEY, []))
                ->map(fn (array $entry): array => [
                    'id' => $entry['id'],
                    'url' => PropertyImage::publicUrl($entry['path']),
                    'thumbnail_url' => PropertyImage::publicUrl($entry['thumbnail_path']),
                ])
                ->values(),
            ...$this->formOptions($request->user()),
        ]);
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $property = $this->propertyService->create(
            $request->propertyData(),
            $request->featureIds(),
            $request->user(),
        );

        return to_route('dashboard.properties.index')
            ->with('success', "Prona {$property->reference_code} u krijua.");
    }

    public function edit(Request $request, Property $property): Response
    {
        Gate::authorize('update', $property);

        $property->load(['features:id', 'location', 'images']);

        return Inertia::render('dashboard/properties/Edit', [
            'property' => [
                'id' => $property->id,
                'reference_code' => $property->reference_code,
                'listing_type' => $property->listing_type->value,
                'is_exclusive' => $property->is_exclusive,
                'category' => $property->category->value,
                'status' => $property->status->value,
                'agent_id' => $property->agent_id,
                'title' => $property->getTranslations('title'),
                'description' => $property->getTranslations('description'),
                'price_euros' => round($property->price / 100, 2),
                'price_negotiable' => $property->price_negotiable,
                'surface_m2' => $property->surface_m2,
                'land_surface_m2' => $property->land_surface_m2,
                'bedrooms' => $property->bedrooms,
                'bathrooms' => $property->bathrooms,
                'floor' => $property->floor,
                'total_floors' => $property->total_floors,
                'year_built' => $property->year_built,
                'parking_spaces' => $property->parking_spaces,
                'location_id' => $property->location_id,
                'municipality_id' => $property->location?->type === LocationType::Municipality
                    ? $property->location->id
                    : $property->location?->parent_id,
                'address_line' => $property->address_line,
                'lat' => $property->lat,
                'lng' => $property->lng,
                'has_possession_sheet' => $property->has_possession_sheet,
                'document_type' => $property->document_type?->value,
                'meta_title' => $property->getTranslations('meta_title'),
                'meta_description' => $property->getTranslations('meta_description'),
                'features' => $property->features->pluck('id'),
            ],
            'images' => $property->images->map(fn (PropertyImage $image): array => [
                'id' => $image->id,
                'url' => $image->url,
                'thumbnail_url' => $image->thumbnail_url,
                'is_primary' => $image->is_primary,
            ])->values(),
            ...$this->formOptions($request->user()),
        ]);
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $this->propertyService->update(
            $property,
            $request->propertyData(),
            $request->featureIds(),
            $request->user(),
        );

        return to_route('dashboard.properties.edit', $property)
            ->with('success', "Prona {$property->reference_code} u përditësua.");
    }

    public function destroy(Request $request, Property $property): RedirectResponse
    {
        Gate::authorize('delete', $property);

        $this->propertyService->delete($property);

        return to_route('dashboard.properties.index')
            ->with('success', "Prona {$property->reference_code} u fshi.");
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(User $user): array
    {
        $municipalities = Location::query()
            ->where('type', LocationType::Municipality)
            ->with(['children' => fn ($query) => $query->orderBy('slug')])
            ->orderBy('slug')
            ->get()
            ->map(fn (Location $municipality): array => [
                'id' => $municipality->id,
                'name' => $municipality->getTranslation('name', 'sq'),
                'lat' => $municipality->lat,
                'lng' => $municipality->lng,
                'neighborhoods' => $municipality->children->map(fn (Location $neighborhood): array => [
                    'id' => $neighborhood->id,
                    'name' => $neighborhood->getTranslation('name', 'sq'),
                    'lat' => $neighborhood->lat,
                    'lng' => $neighborhood->lng,
                ]),
            ]);

        $features = Feature::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (Feature $feature): string => $feature->group->value)
            ->map(fn ($group) => $group->map(fn (Feature $feature): array => [
                'id' => $feature->id,
                'name' => $feature->getTranslation('name', 'sq'),
            ])->values());

        return [
            'municipalities' => $municipalities,
            'features' => $features,
            'agents' => $this->agentOptions($user),
        ];
    }

    /**
     * Assignable users for the admin-only agent filter and reassign field.
     *
     * @return list<array{id: int, name: string}>|null
     */
    private function agentOptions(User $user): ?array
    {
        if (! $user->isAdmin()) {
            return null;
        }

        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $agent): array => ['id' => $agent->id, 'name' => $agent->name])
            ->all();
    }
}
