<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyImage>
 */
class PropertyImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seed = fake()->unique()->numberBetween(1, 1_000_000);

        return [
            'property_id' => Property::factory(),
            'path' => "https://picsum.photos/seed/property-{$seed}/1920/1080",
            'thumbnail_path' => "https://picsum.photos/seed/property-{$seed}/400/267",
            'is_primary' => false,
            'sort_order' => 0,
            'alt_text' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
