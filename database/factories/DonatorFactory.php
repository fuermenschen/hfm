<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\=Donator>
 */
class DonatorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "first_name" => fake()->firstName(),
            "last_name" => fake()->lastName(),
            "address" => fake()->address(),
            "zip_code" => fake()->postcode(),
            "city" => fake()->city(),
            "phone_number" => fake()->phoneNumber(),
            "email" => fake()->unique()->safeEmail(),
        ];
    }
}
