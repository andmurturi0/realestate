<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderPropertyImagesRequest;
use App\Http\Requests\Dashboard\StorePropertyImageRequest;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PropertyImageController extends Controller
{
    public function __construct(private readonly ImageService $imageService) {}

    public function store(StorePropertyImageRequest $request, Property $property): RedirectResponse
    {
        $this->imageService->store($property, $request->file('image'));

        return back();
    }

    public function reorder(ReorderPropertyImagesRequest $request, Property $property): RedirectResponse
    {
        $this->imageService->reorder($property, $request->orderedIds());

        return back();
    }

    public function makePrimary(Property $property, PropertyImage $image): RedirectResponse
    {
        Gate::authorize('update', $property);

        $this->imageService->makePrimary($image);

        return back();
    }

    public function destroy(Property $property, PropertyImage $image): RedirectResponse
    {
        Gate::authorize('update', $property);

        $this->imageService->delete($image);

        return back()->with('success', 'Fotoja u fshi.');
    }
}
