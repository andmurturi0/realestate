<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Property;

class StorePropertyImageRequest extends PropertyImageUploadRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('property')) ?? false;
    }

    protected function currentImageCount(): int
    {
        /** @var Property $property */
        $property = $this->route('property');

        return $property->images()->count();
    }
}
