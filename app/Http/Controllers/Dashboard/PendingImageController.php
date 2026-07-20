<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StorePendingImageRequest;
use App\Models\Property;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Uploads made on the create form, before the property exists: images are
 * processed immediately, parked in a temp directory, and remembered in the
 * session until PropertyService attaches them on save.
 */
class PendingImageController extends Controller
{
    public function __construct(private readonly ImageService $imageService) {}

    public function store(StorePendingImageRequest $request): RedirectResponse
    {
        $this->imageService->storePending($request->file('image'));

        return back();
    }

    public function destroy(string $id): RedirectResponse
    {
        Gate::authorize('create', Property::class);

        $this->imageService->destroyPending($id);

        return back();
    }
}
