<?php

namespace Database\Factories;

use App\Enums\ListingType;
use App\Enums\OfferStatus;
use App\Enums\PropertyCategory;
use App\Models\Location;
use App\Models\PropertyOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyOffer>
 */
class PropertyOfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '+3834'.fake()->numberBetween(3, 9).fake()->numerify('######'),
            'listing_type' => fake()->randomElement(ListingType::cases()),
            'category' => fake()->randomElement(PropertyCategory::cases()),
            'location_id' => Location::factory(),
            'surface_m2' => fake()->numberBetween(40, 500),
            'asking_price' => fake()->numberBetween(200, 4000) * 500 * 100,
            'status' => OfferStatus::New,
            'assigned_to' => null,
            'converted_property_id' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
