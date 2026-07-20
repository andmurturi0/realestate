<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\Property;
use App\Services\ContactMessageService;
use Illuminate\Http\JsonResponse;

class ContactMessageController extends Controller
{
    public function __construct(private readonly ContactMessageService $contactMessageService) {}

    public function store(StoreContactMessageRequest $request, Property $property): JsonResponse
    {
        $this->contactMessageService->submit($property, $request->validated(), $request->ip());

        return response()->json(['status' => 'ok']);
    }
}
