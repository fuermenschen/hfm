<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'logo_light_filename' => fake()->slug().'_light.svg',
            'logo_dark_filename' => fake()->slug().'_dark.svg',
            'beneficiary_blurb' => fake()->sentence(12),
            'url' => fake()->optional()->url(),
        ];
    }
}
