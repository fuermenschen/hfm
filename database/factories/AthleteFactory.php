<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Athlete>
 */
class AthleteFactory extends Factory
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
            "address" => fake()->streetAddress(),
            "zip_code" => fake()->postcode(),
            "city" => fake()->city(),
            "phone_number" => fake()->phoneNumber(),
            "email" => fake()->email(),
            "sport_type" => fake()->randomElement([1, 2, 3, 4]),
            "age" => fake()->numberBetween(18, 50),
            "sponsoring_token" => fake()->numberBetween(100000000, 999999999),
            "email_verified_at" => null,
            "comment" => null,
        ];
    }
}
