<?php

namespace App\Http\Controllers\Dashboard\Inbox;

use App\Enums\LocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Inbox\AssignLeadRequest;
use App\Http\Requests\Dashboard\Inbox\StoreLeadNoteRequest;
use App\Http\Requests\Dashboard\Inbox\UpdateLeadStatusRequest;
use App\Http\Requests\Dashboard\StorePropertyRequest;
use App\Models\Property;
use App\Models\PropertyOffer;
use App\Services\FacetOptionsService;
use App\Services\LeadInboxService;
use App\Services\PropertyOfferConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    /** Albanian category labels — mirrors resources/js/lib/property.ts categoryLabels. */
    private const CATEGORY_LABELS = [
        'house' => 'Shtëpi',
        'apartment' => 'Banesë',
        'office' => 'Zyrë',
        'store' => 'Lokal',
        'land' => 'Tokë',
        'warehouse' => 'Depo',
        'object' => 'Objekt',
    ];

    public function __construct(
        private readonly LeadInboxService $inboxService,
        private readonly PropertyOfferConversionService $conversionService,
        private readonly FacetOptionsService $facetOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', PropertyOffer::class);

        $user = $request->user();
        $filters = $request->only(['status', 'assigned_to', 'date_from', 'date_to', 'search']);

        $query = $this->inboxService->filter(
            $this->inboxService->scope(PropertyOffer::query(), $user),
            $filters,
            ['first_name', 'last_name']
        );

        $offers = $query->with(['assignedTo:id,name', 'location:id,parent_id,name'])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PropertyOffer $offer): array => [
                'id' => $offer->id,
                'name' => "{$offer->first_name} {$offer->last_name}",
                'phone' => $offer->phone,
                'category' => $offer->category->value,
                'listing_type' => $offer->listing_type->value,
                'surface_m2' => $offer->surface_m2,
                'asking_price' => $offer->asking_price,
                'status' => $offer->status->value,
                'assigned_agent' => $offer->assignedTo?->name,
                'converted_property_id' => $offer->converted_property_id,
                'created_at' => $offer->created_at?->toIso8601String(),
            ]);

        return Inertia::render('dashboard/inbox/Offers', [
            'offers' => $offers,
            'filters' => $filters,
            'agents' => $this->inboxService->agentOptions($user),
            'unassignedCount' => $user->isAdmin()
                ? PropertyOffer::query()->whereNull('assigned_to')->where('status', 'new')->count()
                : null,
            'selected' => fn () => $this->presentSelected($request),
        ]);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, PropertyOffer $offer): RedirectResponse
    {
        Gate::authorize('update', $offer);

        $this->inboxService->updateStatus($offer, $request->validated('status'));

        return back();
    }

    public function assign(AssignLeadRequest $request, PropertyOffer $offer): RedirectResponse
    {
        Gate::authorize('assign', $offer);

        $this->inboxService->assign($offer, $request->validated('assigned_to'));

        return back();
    }

    public function storeNote(StoreLeadNoteRequest $request, PropertyOffer $offer): RedirectResponse
    {
        Gate::authorize('update', $offer);

        $this->inboxService->addNote($offer, $request->user(), $request->validated('body'));

        return back();
    }

    /**
     * "Krijo pronë nga oferta": a property create form prefilled from the
     * offer. Nothing is written here — this is a pure GET read, so an
     * agent who navigates away leaves the offer exactly as it was.
     */
    public function createFromOffer(Request $request, PropertyOffer $offer): Response
    {
        Gate::authorize('update', $offer);
        Gate::authorize('create', Property::class);

        $offer->load('location');

        $municipalityId = $offer->location->type === LocationType::Municipality
            ? $offer->location->id
            : $offer->location->parent_id;

        return Inertia::render('dashboard/inbox/ConvertOffer', [
            'offer' => [
                'id' => $offer->id,
                'name' => "{$offer->first_name} {$offer->last_name}",
            ],
            'property' => [
                'listing_type' => $offer->listing_type->value,
                'is_exclusive' => false,
                'category' => $offer->category->value,
                'status' => 'draft',
                'title' => ['sq' => $this->generatedTitle($offer)],
                'description' => [],
                'price_euros' => round($offer->asking_price / 100, 2),
                'price_negotiable' => false,
                'surface_m2' => $offer->surface_m2,
                'location_id' => $offer->location_id,
                'municipality_id' => $municipalityId,
                'lat' => $offer->location->lat,
                'lng' => $offer->location->lng,
                'meta_title' => [],
                'meta_description' => [],
                'features' => [],
            ],
            ...$this->formOptions(),
        ]);
    }

    public function convert(StorePropertyRequest $request, PropertyOffer $offer): RedirectResponse
    {
        Gate::authorize('update', $offer);

        $property = $this->conversionService->convert(
            $offer,
            $request->propertyData(),
            $request->featureIds(),
            $request->user(),
        );

        return to_route('dashboard.properties.edit', $property)
            ->with('success', "Prona {$property->reference_code} u krijua nga oferta. Shtoni foto më poshtë.");
    }

    private function generatedTitle(PropertyOffer $offer): string
    {
        $category = self::CATEGORY_LABELS[$offer->category->value] ?? $offer->category->value;
        $location = $offer->location->getTranslation('name', 'sq');

        return "{$category} në {$location}";
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presentSelected(Request $request): ?array
    {
        $id = $request->query('selected');

        if (! $id) {
            return null;
        }

        $offer = PropertyOffer::query()
            ->with([
                'assignedTo:id,name',
                'location:id,parent_id,name',
                'convertedProperty:id,slug,reference_code',
                'notes' => fn ($q) => $q->with('user:id,name'),
            ])
            ->find($id);

        if ($offer === null) {
            return null;
        }

        Gate::authorize('view', $offer);

        return [
            'id' => $offer->id,
            'first_name' => $offer->first_name,
            'last_name' => $offer->last_name,
            'phone' => $offer->phone,
            'listing_type' => $offer->listing_type->value,
            'category' => $offer->category->value,
            'location' => $offer->location->getTranslation('name', 'sq'),
            'surface_m2' => $offer->surface_m2,
            'asking_price' => $offer->asking_price,
            'status' => $offer->status->value,
            'assigned_to' => $offer->assigned_to,
            'created_at' => $offer->created_at?->toIso8601String(),
            'can_assign' => Gate::allows('assign', $offer),
            'converted_property' => $offer->convertedProperty ? [
                'slug' => $offer->convertedProperty->slug,
                'reference_code' => $offer->convertedProperty->reference_code,
            ] : null,
            'notes' => $offer->notes->map(fn ($note): array => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->user->name,
                'created_at' => $note->created_at?->toIso8601String(),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'municipalities' => $this->facetOptionsService->municipalitiesWithCoordinates(),
            'features' => $this->facetOptionsService->featuresGroupedForDashboard(),
            // The converting agent is fixed server-side (PropertyOfferConversionService),
            // never chosen from the form — no agent picker on this page.
            'agents' => null,
        ];
    }
}
