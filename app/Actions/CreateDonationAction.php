<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Notifications\AthleteNewDonation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Propaganistas\LaravelPhone\PhoneNumber;

class CreateDonationAction
{
    private const ExistingDonationMessage = 'Du unterstützt diese:n Sportler:in für diesen Anlass bereits. Bitte öffne dein Portal, um deine Spenden zu prüfen.';

    /**
     * @param  array{first_name: string, last_name: string, address: string, zip_code: string, city: string, country_of_residence: string, phone_number: string, email: string}|null  $externalUserData
     * @param  array{athlete_registration_id: int, amount_per_round: float, amount_min: float|null, amount_max: float|null, comment: string|null}  $data
     */
    public function __invoke(DonationEvent $donationEvent, ?ExternalUser $externalUser, array $data, ?array $externalUserData = null): Donation
    {
        if (! $donationEvent->donorRegistrationIsOpen()) {
            throw ValidationException::withMessages([
                'donation' => 'Die Anmeldung als Spender:in ist aktuell nicht offen.',
            ]);
        }

        $externalUserData = $this->normalizeExternalUserData($externalUser, $externalUserData);

        $athleteRegistration = $this->validateAthleteRegistration($donationEvent, $data['athlete_registration_id']);

        try {
            $donation = DB::transaction(function () use ($externalUser, $externalUserData, $data, $athleteRegistration): Donation {
                $externalUser ??= ExternalUser::query()->create($externalUserData);

                return Donation::query()->create([
                    'donor_external_user_id' => $externalUser->id,
                    'athlete_registration_id' => $athleteRegistration->id,
                    'amount_per_round' => $data['amount_per_round'],
                    'amount_min' => $data['amount_min'],
                    'amount_max' => $data['amount_max'],
                    'comment' => $data['comment'],
                    'verified' => false,
                ]);
            });

            $this->notifyAthlete($donation);

            return $donation;
        } catch (QueryException $queryException) {
            throw_if($queryException->getCode() !== '23000', $queryException);

            throw ValidationException::withMessages([
                'athlete_registration_id' => self::ExistingDonationMessage,
            ]);
        }
    }

    protected function notifyAthlete(Donation $donation): void
    {
        $donation->loadMissing([
            'athleteRegistration.externalUser',
            'donorExternalUser',
        ]);

        $athlete = $donation->athleteRegistration->externalUser;
        $donor = $donation->donorExternalUser;

        $athlete->notify(new AthleteNewDonation(
            $athlete->first_name,
            $donor->privacy_name,
            $athlete->public_id_string,
        ));
    }

    /**
     * @param  array{first_name: string, last_name: string, address: string, zip_code: string, city: string, country_of_residence: string, phone_number: string, email: string}|null  $externalUserData
     * @return array{first_name: string, last_name: string, address: string, zip_code: string, city: string, country_of_residence: string, phone_number: string, email: string}|null
     */
    protected function normalizeExternalUserData(?ExternalUser $externalUser, ?array $externalUserData): ?array
    {
        if ($externalUser instanceof ExternalUser) {
            return null;
        }

        if ($externalUserData === null) {
            throw ValidationException::withMessages([
                'donation' => 'Bitte melde dich erneut an.',
            ]);
        }

        $externalUserData['email'] = trim(mb_strtolower($externalUserData['email']));

        $knownExternalUser = ExternalUser::withTrashed()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$externalUserData['email']])
            ->first();

        if ($knownExternalUser instanceof ExternalUser) {
            throw ValidationException::withMessages([
                'email' => $knownExternalUser->trashed()
                    ? 'Diese E-Mail-Adresse kann nicht automatisch weiterverwendet werden. Bitte kontaktiere uns, damit wir dein Profil prüfen können.'
                    : 'Diese E-Mail-Adresse ist bereits bekannt. Bitte verwende den Login-Link oder öffne dein Portal.',
            ]);
        }

        return $externalUserData;
    }

    protected function validateAthleteRegistration(DonationEvent $donationEvent, int $athleteRegistrationId): AthleteRegistration
    {
        return AthleteRegistration::query()
            ->whereBelongsTo($donationEvent)
            ->whereKey($athleteRegistrationId)
            ->where('verified', true)
            ->firstOr(fn () => throw ValidationException::withMessages([
                'athlete_registration_id' => 'Die gewählte Sportler:in ist für den aktuellen Anlass nicht verfügbar oder noch nicht bestätigt.',
            ]));
    }

    public static function formatPhoneNumber(string $phoneNational, string $phoneCountry): string
    {
        $phoneNumber = new PhoneNumber($phoneNational, $phoneCountry);

        return $phoneNumber->formatInternational();
    }
}
