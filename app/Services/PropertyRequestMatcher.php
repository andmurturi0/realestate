<?php

namespace App\Services;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyRequest;
use Illuminate\Database\Eloquent\Collection;

class PropertyRequestMatcher
{
    /**
     * Published properties matching a request: same category, same
     * municipality (a single neighbourhood is too narrow to expect exact
     * matches — widen to the whole municipality, the same technique
     * PropertyService::similarTo() uses), price within budget, and
     * surface within the requested range. No relaxation: a request with
     * zero matches says so plainly (FAZAT.md 7B) rather than silently
     * broadening the criteria the way "similar properties" does.
     *
     * @return Collection<int, Property>
     */
    public function matches(PropertyRequest $request, int $limit = 6): Collection
    {
        $request->loadMissing('location');
        $location = $request->location;

        if ($location === null) {
            return new Collection;
        }

        $municipalityId = $location->type === LocationType::Municipality ? $location->id : $location->parent_id;

        $locationIds = Location::query()
            ->where(fn ($query) => $query->whereKey($municipalityId)->orWhere('parent_id', $municipalityId))
            ->pluck('id');

        return Property::query()
            ->published()
            ->where('category', $request->category)
            ->whereIn('location_id', $locationIds)
            ->where('price', '<=', $request->budget_max)
            ->when($request->surface_min_m2 !== null, fn ($q) => $q->where('surface_m2', '>=', $request->surface_min_m2))
            ->when($request->surface_max_m2 !== null, fn ($q) => $q->where('surface_m2', '<=', $request->surface_max_m2))
            ->with([
                'agent:id,name,phone,whatsapp',
                'location:id,parent_id,name,type',
                'location.parent:id,name',
                'primaryImage',
            ])
            // Best price fit: the closest to (but not over) the budget.
            ->orderByDesc('price')
            ->limit($limit)
            ->get();
    }
}
