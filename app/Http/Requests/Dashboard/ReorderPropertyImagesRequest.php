<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderPropertyImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('property')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Property $property */
        $property = $this->route('property');

        return [
            'order' => ['required', 'array'],
            'order.*' => [
                'integer',
                'distinct',
                Rule::exists('property_images', 'id')->where('property_id', $property->id),
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public function orderedIds(): array
    {
        return array_map(intval(...), $this->validated('order'));
    }
}
