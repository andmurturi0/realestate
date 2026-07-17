<?php

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'parent_id' => null,
            'name' => ['sq' => $name, 'en' => $name, 'de' => $name],
            'slug' => Str::slug($name),
            'lat' => fake()->randomFloat(7, 42.0, 43.2),
            'lng' => fake()->randomFloat(7, 20.0, 21.8),
            'type' => LocationType::Municipality,
        ];
    }

    public function neighborhood(?Location $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Location::factory(),
            'type' => LocationType::Neighborhood,
        ]);
    }
}
