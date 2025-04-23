<?php

namespace Database\Factories;

use App\Models\Athlete;
use App\Models\Donator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\=Donation>
 */
class DonationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $verified_athletes = Athlete::where('verified', 1)->get();
        $donators = Donator::all();

        return [
            "donator_id" => $donators->random()->id,
            "athlete_id" => $verified_athletes->random()->id,
            "amount_per_round" => fake()->randomFloat(2, 0, 100),
            "amount_max" => fake()->randomFloat(2, 0, 100),
            "amount_min" => fake()->randomFloat(2, 0, 100),
            "comment" => fake()->text(100),
            "verified" => fake()->boolean(),
        ];
    }
}
