<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAthleteRegistrationAction
{
    private const ExistingRegistrationMessage = 'Du bist für diesen Anlass bereits als Sportler:in angemeldet. Bitte öffne dein Portal, um deine Anmeldung zu prüfen.';

    /**
     * @param  array{first_name: string, last_name: string, address: string, zip_code: string, city: string, country_of_residence: string, phone_number: string, email: string}|null  $externalUserData
     * @param  array{sport_type_id: int, rounds_estimated: int, partner_id: int|null, comment: string|null, notify_previous_donors: bool}  $data
     */
    public function __invoke(DonationEvent $donationEvent, ?ExternalUser $externalUser, array $data, ?array $externalUserData = null): AthleteRegistration
    {
        if (! $donationEvent->athleteRegistrationIsOpen()) {
            throw ValidationException::withMessages([
                'registration' => 'Die Anmeldung als Sportler:in ist aktuell nicht offen.',
            ]);
        }

        $externalUserData = $this->normalizeExternalUserData($externalUser, $externalUserData);

        if ($externalUser instanceof ExternalUser) {
            $existingRegistration = AthleteRegistration::query()
                ->whereBelongsTo($donationEvent)
                ->whereBelongsTo($externalUser)
                ->first();

            if ($existingRegistration instanceof AthleteRegistration) {
                throw ValidationException::withMessages([
                    'registration' => self::ExistingRegistrationMessage,
                ]);
            }
        }

        $partnerId = $this->normalizePartnerId($donationEvent, $data['partner_id']);
        $this->validateSportType($donationEvent, $data['sport_type_id']);

        try {
            return DB::transaction(function () use ($donationEvent, $externalUser, $externalUserData, $data, $partnerId): AthleteRegistration {
                $externalUser ??= ExternalUser::query()->create($externalUserData);

                return AthleteRegistration::query()->create([
                    'donation_event_id' => $donationEvent->id,
                    'external_user_id' => $externalUser->id,
                    'sport_type_id' => $data['sport_type_id'],
                    'rounds_estimated' => $data['rounds_estimated'],
                    'rounds_done' => 0,
                    'partner_id' => $partnerId,
                    'comment' => $data['comment'],
                    'notify_previous_donors' => $data['notify_previous_donors'],
                    'verified' => false,
                ]);
            });
        } catch (QueryException $queryException) {
            if ($queryException->getCode() === '23000') {
                $this->throwClassifiedIntegrityValidationException($donationEvent, $externalUser, $externalUserData, $queryException);
            }

            throw $queryException;
        }
    }

    /**
     * @param  array{first_name: string, last_name: string, address: string, zip_code: string, city: string, country_of_residence: string, phone_number: string, email: string}|null  $externalUserData
     */
    protected function throwClassifiedIntegrityValidationException(DonationEvent $donationEvent, ?ExternalUser $externalUser, ?array $externalUserData, QueryException $queryException): never
    {
        if ($externalUser instanceof ExternalUser && AthleteRegistration::query()
            ->whereBelongsTo($donationEvent)
            ->whereBelongsTo($externalUser)
            ->exists()) {
            throw ValidationException::withMessages([
                'registration' => self::ExistingRegistrationMessage,
            ]);
        }

        if ($externalUserData !== null && ExternalUser::withTrashed()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$externalUserData['email']])
            ->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Diese E-Mail-Adresse ist bereits bekannt. Bitte verwende den Login-Link oder öffne dein Portal.',
            ]);
        }

        throw $queryException;
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
                'registration' => 'Bitte melde dich erneut an.',
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

    protected function normalizePartnerId(DonationEvent $donationEvent, ?int $partnerId): ?int
    {
        if ($partnerId === 0) {
            if (! $donationEvent->has_equal_split_option) {
                throw ValidationException::withMessages([
                    'partner_id' => 'Diese Auswahl ist für den aktuellen Anlass nicht verfügbar.',
                ]);
            }

            return null;
        }

        $isEventPartner = $donationEvent->partners()
            ->whereKey($partnerId)
            ->wherePivot('is_published', true)
            ->exists();

        if (! $isEventPartner) {
            throw ValidationException::withMessages([
                'partner_id' => 'Die gewählte Partner:in ist für den aktuellen Anlass nicht verfügbar.',
            ]);
        }

        return $partnerId;
    }

    protected function validateSportType(DonationEvent $donationEvent, int $sportTypeId): void
    {
        $isEventSportType = $donationEvent->sportTypes()
            ->whereKey($sportTypeId)
            ->wherePivot('is_enabled', true)
            ->exists();

        if (! $isEventSportType) {
            throw ValidationException::withMessages([
                'sport_type_id' => 'Die gewählte Sportart ist für den aktuellen Anlass nicht verfügbar.',
            ]);
        }
    }
}
