<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\DocumentType;
use App\Enums\ListingType;
use App\Enums\PropertyCategory;
use App\Enums\PropertyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class PropertyRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'listing_type' => ['required', Rule::enum(ListingType::class)],
            'is_exclusive' => ['boolean'],
            'category' => ['required', Rule::enum(PropertyCategory::class)],
            'status' => ['required', Rule::enum(PropertyStatus::class)],

            'title' => ['required', 'array'],
            'title.sq' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.de' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'array'],
            'description.sq' => ['required', 'string', 'max:10000'],
            'description.en' => ['nullable', 'string', 'max:10000'],
            'description.de' => ['nullable', 'string', 'max:10000'],

            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            'price_negotiable' => ['boolean'],

            'surface_m2' => ['required', 'numeric', 'min:1', 'max:99999999'],

            'location_id' => ['required', Rule::exists('locations', 'id')],
            'address_line' => ['nullable', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],

            'has_possession_sheet' => ['nullable', 'boolean'],
            'document_type' => ['nullable', Rule::enum(DocumentType::class)],

            'features' => ['nullable', 'array'],
            'features.*' => ['integer', Rule::exists('features', 'id')],

            'meta_title' => ['nullable', 'array'],
            'meta_title.sq' => ['nullable', 'string', 'max:255'],
            'meta_title.en' => ['nullable', 'string', 'max:255'],
            'meta_title.de' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'array'],
            'meta_description.sq' => ['nullable', 'string', 'max:500'],
            'meta_description.en' => ['nullable', 'string', 'max:500'],
            'meta_description.de' => ['nullable', 'string', 'max:500'],

            ...$this->detailFieldRules(),
        ];

        // agent_id gets a rule — and thereby a way into validated() — only for
        // admins. For agents a forged agent_id in the payload is dead on arrival.
        if ($this->user()?->isAdmin()) {
            $rules['agent_id'] = ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')];
        }

        return $rules;
    }

    /**
     * Category-dependent fields: allowed ones get their numeric rules,
     * the rest are prohibited so land can never carry bedrooms — even
     * when the UI is bypassed.
     *
     * @return array<string, list<mixed>>
     */
    protected function detailFieldRules(): array
    {
        $category = PropertyCategory::tryFrom((string) $this->input('category'));

        $numericRules = [
            'land_surface_m2' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'floor' => ['nullable', 'integer', 'min:-5', 'max:200'],
            'total_floors' => ['nullable', 'integer', 'min:1', 'max:200'],
            'year_built' => ['nullable', 'integer', 'min:1800', 'max:'.(now()->year + 5)],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];

        $rules = [];

        foreach (PropertyCategory::DETAIL_FIELDS as $field) {
            $rules[$field] = $category === null || $category->allowsDetailField($field)
                ? $numericRules[$field]
                : ['prohibited'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $prohibited = 'Kjo fushë nuk lejohet për kategorinë e zgjedhur.';

        return [
            'title.sq.required' => 'Titulli në shqip është i detyrueshëm.',
            'description.sq.required' => 'Përshkrimi në shqip është i detyrueshëm.',
            'land_surface_m2.prohibited' => $prohibited,
            'bedrooms.prohibited' => $prohibited,
            'bathrooms.prohibited' => $prohibited,
            'floor.prohibited' => $prohibited,
            'total_floors.prohibited' => $prohibited,
            'year_built.prohibited' => $prohibited,
            'parking_spaces.prohibited' => $prohibited,
        ];
    }

    /**
     * The validated attributes ready for persistence: the euro price is
     * converted to integer cents, detail fields the category does not
     * allow are forced to null (a category switch must clear stale
     * values), and empty translations are dropped so locale fallback
     * works. Features are excluded — they sync through the pivot.
     *
     * @return array<string, mixed>
     */
    public function propertyData(): array
    {
        $data = $this->safe()->except(['features']);

        $data['price'] = (int) round((float) $data['price'] * 100);

        $category = PropertyCategory::from($data['category']);

        foreach (PropertyCategory::DETAIL_FIELDS as $field) {
            if (! $category->allowsDetailField($field)) {
                $data[$field] = null;
            }
        }

        foreach (['title', 'description', 'meta_title', 'meta_description'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = array_filter(
                    $data[$field] ?? [],
                    fn (?string $value): bool => $value !== null && $value !== ''
                );
            }
        }

        return $data;
    }

    /**
     * @return list<int>
     */
    public function featureIds(): array
    {
        return array_map(intval(...), $this->validated('features') ?? []);
    }
}
