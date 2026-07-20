<?php

namespace App\Services;

use App\Enums\OfferStatus;
use App\Models\Property;
use App\Models\PropertyOffer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PropertyOfferConversionService
{
    public function __construct(private readonly PropertyService $propertyService) {}

    /**
     * Converts an offer into a property. Nothing is written until this
     * runs — the "Krijo pronë nga oferta" GET page only ever reads the
     * offer to prefill a form, so an agent abandoning the form leaves the
     * offer untouched. Everything below happens in one transaction: if the
     * offer update fails, the just-created property is rolled back too.
     *
     * property.agent_id is always the converting agent — FAZAT.md is
     * explicit about this, so it is forced here regardless of what an
     * admin's agent_id field on the form might have carried.
     *
     * @param  array<string, mixed>  $propertyData
     * @param  list<int>  $featureIds
     */
    public function convert(PropertyOffer $offer, array $propertyData, array $featureIds, User $agent): Property
    {
        $propertyData['agent_id'] = $agent->id;

        return DB::transaction(function () use ($offer, $propertyData, $featureIds, $agent): Property {
            $property = $this->propertyService->create($propertyData, $featureIds, $agent);

            $offer->update([
                'converted_property_id' => $property->id,
                'status' => OfferStatus::Converted,
            ]);

            return $property;
        });
    }
}
