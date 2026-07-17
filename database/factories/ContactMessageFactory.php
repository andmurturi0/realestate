<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => null,
            'full_name' => fake()->name(),
            'phone' => '+3834'.fake()->numberBetween(3, 9).fake()->numerify('######'),
            'email' => fake()->optional(0.6)->safeEmail(),
            'message' => fake()->paragraph(),
            'status' => LeadStatus::New,
            'assigned_to' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
