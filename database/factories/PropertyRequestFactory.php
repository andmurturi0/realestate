<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Enums\PropertyCategory;
use App\Enums\RequestType;
use App\Enums\SurfaceUnit;
use App\Models\Location;
use App\Models\PropertyRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyRequest>
 */
class PropertyRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $surfaceMin = fake()->numberBetween(30, 100);

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '+3834'.fake()->numberBetween(3, 9).fake()->numerify('######'),
            'request_type' => fake()->randomElement(RequestType::cases()),
            'category' => fake()->randomElement(PropertyCategory::cases()),
            'location_id' => Location::factory(),
            'budget_max' => fake()->numberBetween(100, 600) * 500 * 100,
            'surface_min_m2' => $surfaceMin,
            'surface_max_m2' => $surfaceMin + fake()->numberBetween(20, 100),
            'surface_unit' => SurfaceUnit::SquareMetre,
            'details' => fake()->optional(0.5)->paragraph(),
            'status' => LeadStatus::New,
            'assigned_to' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
