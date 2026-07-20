<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Property;
use App\Services\ImageService;

class StorePendingImageRequest extends PropertyImageUploadRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Property::class) ?? false;
    }

    protected function currentImageCount(): int
    {
        return count($this->session()->get(ImageService::PENDING_SESSION_KEY, []));
    }
}
