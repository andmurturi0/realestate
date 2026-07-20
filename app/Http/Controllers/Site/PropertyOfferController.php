<?php

namespace App\Http\Controllers\Site;

use App\Enums\LocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyOfferRequest;
use App\Models\Location;
use App\Services\PropertyOfferService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyOfferController extends Controller
{
    public function __construct(private readonly PropertyOfferService $propertyOfferService) {}

    public function create(): Response
    {
        return Inertia::render('OfferProperty', [
            'municipalities' => $this->locationOptions(),
        ]);
    }

    public function store(StorePropertyOfferRequest $request): JsonResponse
    {
        $this->propertyOfferService->submit($request->offerData(), $request->isBotSubmission(), $request->ip());

        return response()->json(['status' => 'ok']);
    }

    /**
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
                'name' => $municipality->getTranslation('name', app()->getLocale()),
                'neighborhoods' => $municipality->children->map(fn (Location $neighborhood): array => [
                    'id' => $neighborhood->id,
                    'name' => $neighborhood->getTranslation('name', app()->getLocale()),
                ])->all(),
            ])
            ->all();
    }
}
