<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SportType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SportType>
 */
class SportTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseName = fake()->randomElement([
            'Running',
            'Cycling',
            'Swimming',
            'Walking',
            'Inline Skating',
            'Rowing',
            'Hiking',
            'Cross-Country Skiing',
            'Snowboarding',
            'Wheelchair Racing',
        ]);

        return [
            'name' => sprintf('%s %s', $baseName, fake()->unique()->numerify('##')),
        ];
    }
}
