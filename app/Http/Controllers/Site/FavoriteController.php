<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\FavoritePropertiesRequest;
use App\Http\Resources\PropertyCardResource;
use App\Models\Property;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    /**
     * The favourites page has no server-side knowledge of which properties
     * are favourited — that list lives only in the visitor's localStorage.
     * The client fetches the actual cards from properties() once mounted.
     */
    public function index(): Response
    {
        return Inertia::render('favorites/Index');
    }

    /**
     * Looks up published properties by id, for the client's localStorage-held
     * favourites list. Ids that are missing, unpublished, or invalid are
     * silently dropped rather than erroring — favourites can go stale.
     */
    public function properties(FavoritePropertiesRequest $request): AnonymousResourceCollection
    {
        $properties = Property::query()
            ->published()
            ->whereIn('id', $request->ids())
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
            ->get();

        return PropertyCardResource::collection($properties);
    }
}
