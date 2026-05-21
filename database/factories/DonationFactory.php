<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Athlete;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\Donor;
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
            'donor_id' => Donor::factory(),
            'athlete_id' => Athlete::factory()->verified(),
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

    public function legacyLinked(): static
    {
        return $this->afterCreating(function (Donation $donation): void {
            if ($donation->donor_id !== null && $donation->athlete_id !== null) {
                return;
            }

            $externalDonor = $donation->donorExternalUser ?? ExternalUser::query()->find($donation->donor_external_user_id);
            $externalAthlete = $donation->athleteRegistration?->externalUser
                ?? ExternalUser::query()->find($donation->athleteRegistration?->external_user_id);

            $donor = Donor::factory()->create([
                'first_name' => $externalDonor?->first_name,
                'last_name' => $externalDonor?->last_name,
                'address' => $externalDonor?->address,
                'zip_code' => $externalDonor?->zip_code,
                'city' => $externalDonor?->city,
                'phone_number' => $externalDonor?->phone_number,
                'email' => $externalDonor?->email,
                'country_of_residence' => $externalDonor?->country_of_residence,
            ]);

            $athlete = Athlete::factory()->verified()->create([
                'first_name' => $externalAthlete?->first_name,
                'last_name' => $externalAthlete?->last_name,
                'address' => $externalAthlete?->address,
                'zip_code' => $externalAthlete?->zip_code,
                'city' => $externalAthlete?->city,
                'phone_number' => $externalAthlete?->phone_number,
                'email' => $externalAthlete?->email,
            ]);

            $donation->forceFill([
                'donor_id' => $donor->id,
                'athlete_id' => $athlete->id,
            ])->save();
        });
    }
}
