<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalUser>
 */
class ExternalUserFactory extends Factory
{
    protected $model = ExternalUser::class;

    public function definition(): array
    {
        $country = fake()->randomElement([
            ...array_fill(0, 9, 'CH'),
            'DE',
            'AT',
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
            'address' => fake()->streetAddress(),
            'zip_code' => $postcode,
            'city' => fake()->city(),
            'country_of_residence' => $country,
            'phone_number' => $phoneNumber,
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
