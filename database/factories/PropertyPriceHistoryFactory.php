<?php

namespace Database\Factories;

use App\Models\PropertyPriceHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyPriceHistory>
 */
class PropertyPriceHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $oldPrice = fake()->numberBetween(50_000, 300_000) * 100;

        return [
            'property_id' => \App\Models\Property::factory(),
            'old_price' => $oldPrice,
            'new_price' => (int) round($oldPrice * fake()->randomFloat(2, 0.85, 1.15), -4),
            'changed_by' => null,
        ];
    }
}
