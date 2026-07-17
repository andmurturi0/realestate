<?php

namespace Database\Factories;

use App\Enums\FeatureGroup;
use App\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feature>
 */
class FeatureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->slug(2);
        $name = str_replace('-', ' ', $key);

        return [
            'key' => $key,
            'name' => ['sq' => $name, 'en' => $name, 'de' => $name],
            'icon' => null,
            'group' => fake()->randomElement(FeatureGroup::cases()),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
