<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssociationMember>
 */
class AssociationMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address' => fake()->streetAddress(),
            'zip_code' => fake()->numberBetween(1000, 9999),
            'city' => fake()->city(),
            'phone_number' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'company_name' => fake()->optional()->company(),
            'comment' => fake()->optional()->text(500),
        ];
    }
}
