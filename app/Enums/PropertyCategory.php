<?php

namespace App\Enums;

enum PropertyCategory: string
{
    case House = 'house';
    case Apartment = 'apartment';
    case Office = 'office';
    case Store = 'store';
    case Land = 'land';
    case Warehouse = 'warehouse';
    case Object = 'object';

    /**
     * Every category-dependent detail column on properties. Fields not
     * allowed for a category are rejected by the FormRequest and nulled
     * on save — hiding them in the UI is UX, this matrix is the guard.
     *
     * @var list<string>
     */
    public const DETAIL_FIELDS = [
        'land_surface_m2',
        'bedrooms',
        'bathrooms',
        'floor',
        'total_floors',
        'year_built',
        'parking_spaces',
    ];

    /**
     * The detail fields this category accepts. Mirrored client-side in
     * resources/js/lib/property.ts — keep the two in sync.
     *
     * @return list<string>
     */
    public function allowedDetailFields(): array
    {
        return match ($this) {
            self::House => ['land_surface_m2', 'bedrooms', 'bathrooms', 'total_floors', 'year_built', 'parking_spaces'],
            self::Apartment => ['bedrooms', 'bathrooms', 'floor', 'total_floors', 'year_built', 'parking_spaces'],
            self::Office, self::Store => ['floor', 'total_floors', 'year_built', 'parking_spaces'],
            self::Land => ['land_surface_m2'],
            self::Warehouse => ['year_built', 'parking_spaces'],
            self::Object => ['land_surface_m2', 'total_floors', 'year_built', 'parking_spaces'],
        };
    }

    public function allowsDetailField(string $field): bool
    {
        return in_array($field, $this->allowedDetailFields(), true);
    }
}
