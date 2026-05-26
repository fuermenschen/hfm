<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\ExternalUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'athlete_id' => null,
            'donor_external_user_id' => ExternalUser::factory(),
            'athlete_registration_id' => AthleteRegistration::factory(),
            'amount_per_round' => fake()->randomFloat(2, 0, 100),
            'amount_max' => fake()->randomFloat(2, 0, 100),
            'amount_min' => fake()->randomFloat(2, 0, 100),
            'comment' => fake()->text(100),
            'verified' => fake()->boolean(),
        ];
    }

    public function forAthleteRegistration(AthleteRegistration|int $registration): static
    {
        $registrationId = $registration instanceof AthleteRegistration ? $registration->id : $registration;

        return $this->state(fn (): array => [
            'athlete_registration_id' => $registrationId,
        ]);
    }

    public function forDonorExternalUser(ExternalUser|int $externalUser): static
    {
        $externalUserId = $externalUser instanceof ExternalUser ? $externalUser->id : $externalUser;

        return $this->state(fn (): array => [
            'donor_external_user_id' => $externalUserId,
        ]);
    }
}
