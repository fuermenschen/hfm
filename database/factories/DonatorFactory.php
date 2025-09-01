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
        $country = fake()->randomElement([
            ...array_fill(0, 9, 'CH'), // 90% CH
            'DE', // 5% DE
            'AT', // 5% AT
        ]);

        $phonePrefixes = [
            'CH' => '+41',
            'DE' => '+49',
            'AT' => '+43',
        ];

        $postcode = $country === 'DE' ? str_pad((string) fake()->numberBetween(0, 99999), 5, '0', STR_PAD_LEFT) : str_pad((string) fake()->numberBetween(1000, 9999), 4, '0', STR_PAD_LEFT);

        $phonePrefix = $phonePrefixes[$country];
        $phoneNumber = $phonePrefix.fake()->numerify(' #########');

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address' => fake()->address(),
            'zip_code' => $postcode,
            'city' => fake()->city(),
            'phone_number' => $phoneNumber,
            'email' => fake()->unique()->safeEmail(),
            'country_of_residence' => $country,
        ];
    }
}
