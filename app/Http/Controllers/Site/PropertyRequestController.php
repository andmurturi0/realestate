<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequestRequest;
use App\Services\FacetOptionsService;
use App\Services\PropertyRequestService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyRequestController extends Controller
{
    public function __construct(
        private readonly PropertyRequestService $propertyRequestService,
        private readonly FacetOptionsService $facetOptionsService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('CreateRequest', [
            'municipalities' => $this->facetOptionsService->municipalities(app()->getLocale()),
        ]);
    }

    public function store(StorePropertyRequestRequest $request): JsonResponse
    {
        $this->propertyRequestService->submit($request->requestData(), $request->isBotSubmission(), $request->ip());

        return response()->json(['status' => 'ok']);
    }
}
