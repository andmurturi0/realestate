<?php

namespace App\Http\Controllers\Site;

use App\Enums\LocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequestRequest;
use App\Models\Location;
use App\Services\PropertyRequestService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyRequestController extends Controller
{
    public function __construct(private readonly PropertyRequestService $propertyRequestService) {}

    public function create(): Response
    {
        return Inertia::render('CreateRequest', [
            'municipalities' => $this->locationOptions(),
        ]);
    }

    public function store(StorePropertyRequestRequest $request): JsonResponse
    {
        $this->propertyRequestService->submit($request->requestData(), $request->isBotSubmission(), $request->ip());

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
