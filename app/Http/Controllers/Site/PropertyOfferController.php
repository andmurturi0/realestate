<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyOfferRequest;
use App\Services\FacetOptionsService;
use App\Services\PropertyOfferService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyOfferController extends Controller
{
    public function __construct(
        private readonly PropertyOfferService $propertyOfferService,
        private readonly FacetOptionsService $facetOptionsService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('OfferProperty', [
            'municipalities' => $this->facetOptionsService->municipalities(app()->getLocale()),
        ]);
    }

    public function store(StorePropertyOfferRequest $request): JsonResponse
    {
        $this->propertyOfferService->submit($request->offerData(), $request->isBotSubmission(), $request->ip());

        return response()->json(['status' => 'ok']);
    }
}
