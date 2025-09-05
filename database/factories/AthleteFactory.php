<?php

namespace Database\Factories;

use App\Models\Athlete;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Athlete>
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address' => fake()->streetAddress(),
            'zip_code' => fake()->postcode(),
            'city' => fake()->city(),
            'phone_number' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'adult' => fake()->boolean(10),
            'sport_type_id' => fake()->numberBetween(1, 4),
            'rounds_estimated' => fake()->numberBetween(1, 10),
            'rounds_done' => 0,
            'partner_id' => fake()->numberBetween(1, 4),
            'comment' => fake()->optional()->text(2000),
            'verified' => fake()->boolean(80),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified' => true,
        ]);
    }
}
