<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
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

    /**
     * Model this external user as a donor: creates one donation linked via donor_external_user_id.
     *
     * @param  array<string, mixed>  $donationAttributes  Extra attributes merged onto the donation.
     */
    public function asDonor(DonationEvent|int|null $event = null, array $donationAttributes = []): static
    {
        return $this->afterCreating(function (ExternalUser $externalUser) use ($event, $donationAttributes): void {
            $registrationAttributes = ['external_user_id' => ExternalUser::factory()->create()->id];

            if ($event !== null) {
                $registrationAttributes['donation_event_id'] = $event instanceof DonationEvent ? $event->id : $event;
            }

            $registration = AthleteRegistration::factory()->create($registrationAttributes);

            Donation::factory()
                ->forDonorExternalUser($externalUser)
                ->forAthleteRegistration($registration)
                ->create($donationAttributes);
        });
    }

    /**
     * Model this external user as an athlete: creates one athlete registration linked via external_user_id.
     *
     * @param  array<string, mixed>  $registrationAttributes  Extra attributes merged onto the registration.
     */
    public function asAthlete(DonationEvent|int|null $event = null, array $registrationAttributes = []): static
    {
        return $this->afterCreating(function (ExternalUser $externalUser) use ($event, $registrationAttributes): void {
            $base = ['external_user_id' => $externalUser->id];

            if ($event !== null) {
                $base['donation_event_id'] = $event instanceof DonationEvent ? $event->id : $event;
            }

            AthleteRegistration::factory()->create(array_merge($base, $registrationAttributes));
        });
    }
}
