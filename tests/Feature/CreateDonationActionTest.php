<?php

use App\Actions\CreateDonationAction;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Notifications\AthleteNewDonation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function createDonorTestEvent(bool $donorRegistrationOpen = false): DonationEvent
{
    $event = DonationEvent::factory()->defaults()->create([
        'registration_opens_at' => $donorRegistrationOpen ? now()->subDay() : now()->addDay(),
        'donor_registration_closes_at' => $donorRegistrationOpen ? now()->addDay() : now()->addDays(2),
    ]);

    $partner = Partner::factory()->create();
    $event->partners()->attach($partner, ['sort_order' => 1, 'is_published' => true]);

    $sportType = SportType::query()->firstOrCreate(['name' => 'Laufen']);
    $event->sportTypes()->attach($sportType, ['sort_order' => 1, 'is_enabled' => true]);

    return $event;
}

function createVerifiedAthleteRegistration(DonationEvent $event): AthleteRegistration
{
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Claudia',
        'last_name' => 'Müller',
    ]);

    $sportType = $event->sportTypes()->first();
    $partner = $event->partners()->first();

    return AthleteRegistration::query()->create([
        'donation_event_id' => $event->id,
        'external_user_id' => $externalUser->id,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'rounds_estimated' => 10,
        'rounds_done' => 0,
        'comment' => 'Ich laufe für den guten Zweck!',
        'notify_previous_donors' => false,
        'verified' => true,
    ]);
}

it('creates donation for new donor with new external user', function (): void {
    $event = createDonorTestEvent(donorRegistrationOpen: true);
    $athleteRegistration = createVerifiedAthleteRegistration($event);
    Notification::fake();

    $action = resolve(CreateDonationAction::class);

    $donation = $action($event, null, [
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 5.00,
        'amount_min' => 50.00,
        'amount_max' => 200.00,
        'comment' => 'Tolle Sache!',
    ], [
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'address' => 'Zelglistrasse 41',
        'zip_code' => '8406',
        'city' => 'Winterthur',
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 123 45 67',
        'email' => 'francesca@example.com',
    ]);

    expect($donation->amount_per_round)->toBe(5.00)
        ->and($donation->amount_min)->toBe(50.00)
        ->and($donation->amount_max)->toBe(200.00)
        ->and($donation->comment)->toBe('Tolle Sache!')
        ->and($donation->verified)->toBeFalse()
        ->and($donation->athlete_registration_id)->toBe($athleteRegistration->id);

    $externalUser = $donation->donorExternalUser;
    expect($externalUser)->toBeInstanceOf(ExternalUser::class)
        ->and($externalUser->first_name)->toBe('Francesca')
        ->and($externalUser->email)->toBe('francesca@example.com');

    Notification::assertSentTo(
        $athleteRegistration->externalUser,
        fn (AthleteNewDonation $notification): bool => $notification->first_name === 'Claudia'
            && $notification->donor_name === 'Francesca A.'
            && $notification->public_id_string === $athleteRegistration->externalUser->public_id_string,
    );
});

it('creates donation for existing external user', function (): void {
    $event = createDonorTestEvent(donorRegistrationOpen: true);
    $athleteRegistration = createVerifiedAthleteRegistration($event);
    $existingUser = ExternalUser::factory()->create(['email' => 'francesca@example.com']);
    Notification::fake();

    $action = resolve(CreateDonationAction::class);

    $donation = $action($event, $existingUser, [
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 7.50,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => null,
    ]);

    expect($donation->donor_external_user_id)->toBe($existingUser->id)
        ->and(ExternalUser::query()->count())->toBe(2);
});

it('throws when donor registration is closed', function (): void {
    $event = createDonorTestEvent(donorRegistrationOpen: false);
    $athleteRegistration = createVerifiedAthleteRegistration($event);

    $action = resolve(CreateDonationAction::class);

    $action($event, null, [
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 5.00,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => null,
    ], [
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'address' => 'Zelglistrasse 41',
        'zip_code' => '8406',
        'city' => 'Winterthur',
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 123 45 67',
        'email' => 'francesca@example.com',
    ]);
})->throws(ValidationException::class, 'Die Anmeldung als Spender:in ist aktuell nicht offen.');

it('throws when athlete registration is not verified', function (): void {
    $event = createDonorTestEvent(donorRegistrationOpen: true);
    $athleteRegistration = createVerifiedAthleteRegistration($event);
    $athleteRegistration->update(['verified' => false]);

    $action = resolve(CreateDonationAction::class);

    $action($event, null, [
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 5.00,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => null,
    ], [
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'address' => 'Zelglistrasse 41',
        'zip_code' => '8406',
        'city' => 'Winterthur',
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 123 45 67',
        'email' => 'francesca@example.com',
    ]);
})->throws(ValidationException::class, 'Die gewählte Sportler:in ist für den aktuellen Anlass nicht verfügbar oder noch nicht bestätigt.');

it('throws when athlete registration belongs to different event', function (): void {
    $event = createDonorTestEvent(donorRegistrationOpen: true);
    $otherEvent = createDonorTestEvent(donorRegistrationOpen: true);
    $athleteRegistration = createVerifiedAthleteRegistration($otherEvent);

    $action = resolve(CreateDonationAction::class);

    $action($event, null, [
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 5.00,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => null,
    ], [
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'address' => 'Zelglistrasse 41',
        'zip_code' => '8406',
        'city' => 'Winterthur',
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 123 45 67',
        'email' => 'francesca@example.com',
    ]);
})->throws(ValidationException::class, 'Die gewählte Sportler:in ist für den aktuellen Anlass nicht verfügbar oder noch nicht bestätigt.');

it('throws when email already belongs to known external user and no external user passed', function (): void {
    $event = createDonorTestEvent(donorRegistrationOpen: true);
    $athleteRegistration = createVerifiedAthleteRegistration($event);
    ExternalUser::factory()->create(['email' => 'francesca@example.com']);

    $action = resolve(CreateDonationAction::class);

    $action($event, null, [
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 5.00,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => null,
    ], [
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'address' => 'Zelglistrasse 41',
        'zip_code' => '8406',
        'city' => 'Winterthur',
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 123 45 67',
        'email' => 'francesca@example.com',
    ]);
})->throws(ValidationException::class, 'Diese E-Mail-Adresse ist bereits bekannt.');

it('allows multiple donations to different athletes in same event', function (): void {
    $event = createDonorTestEvent(donorRegistrationOpen: true);
    $athlete1 = createVerifiedAthleteRegistration($event);
    $athlete2 = createVerifiedAthleteRegistration($event);
    $donor = ExternalUser::factory()->create();
    Notification::fake();

    $action = resolve(CreateDonationAction::class);

    $donation1 = $action($event, $donor, [
        'athlete_registration_id' => $athlete1->id,
        'amount_per_round' => 5.00,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => null,
    ]);

    $donation2 = $action($event, $donor, [
        'athlete_registration_id' => $athlete2->id,
        'amount_per_round' => 7.00,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => null,
    ]);

    expect($donation1->id)->not->toBe($donation2->id)
        ->and($donation1->donor_external_user_id)->toBe($donor->id)
        ->and($donation2->donor_external_user_id)->toBe($donor->id)
        ->and(Donation::query()->where('donor_external_user_id', $donor->id)->count())->toBe(2);
});

it('throws on duplicate donation to same athlete', function (): void {
    $event = createDonorTestEvent(donorRegistrationOpen: true);
    $athleteRegistration = createVerifiedAthleteRegistration($event);
    $donor = ExternalUser::factory()->create();
    Notification::fake();

    $action = resolve(CreateDonationAction::class);

    $action($event, $donor, [
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 5.00,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => null,
    ]);

    $action($event, $donor, [
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 7.00,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => null,
    ]);
})->throws(ValidationException::class, 'Du unterstützt diese:n Sportler:in für diesen Anlass bereits.');

it('shows email error when duplicate email appears during donation creation', function (): void {
    $event = createDonorTestEvent(donorRegistrationOpen: true);
    $athleteRegistration = createVerifiedAthleteRegistration($event);

    $action = new class extends CreateDonationAction
    {
        /**
         * @param  array{first_name: string, last_name: string, address: string, zip_code: string, city: string, country_of_residence: string, phone_number: string, email: string}|null  $externalUserData
         * @return array{first_name: string, last_name: string, address: string, zip_code: string, city: string, country_of_residence: string, phone_number: string, email: string}|null
         */
        protected function normalizeExternalUserData(?ExternalUser $externalUser, ?array $externalUserData): ?array
        {
            $externalUserData = parent::normalizeExternalUserData($externalUser, $externalUserData);

            ExternalUser::factory()->create(['email' => $externalUserData['email']]);

            return $externalUserData;
        }
    };

    $hasEmailError = false;

    try {
        $action($event, null, [
            'athlete_registration_id' => $athleteRegistration->id,
            'amount_per_round' => 5.00,
            'amount_min' => null,
            'amount_max' => null,
            'comment' => null,
        ], [
            'first_name' => 'Francesca',
            'last_name' => 'Arslan',
            'address' => 'Zelglistrasse 41',
            'zip_code' => '8406',
            'city' => 'Winterthur',
            'country_of_residence' => 'CH',
            'phone_number' => '+41 79 123 45 67',
            'email' => 'francesca@example.com',
        ]);
    } catch (ValidationException $validationException) {
        $hasEmailError = array_key_exists('email', $validationException->errors());
    }

    expect($hasEmailError)->toBeTrue();
});

it('rethrows unrelated integrity errors during donation creation', function (): void {
    $event = createDonorTestEvent(donorRegistrationOpen: true);
    $athleteRegistration = createVerifiedAthleteRegistration($event);
    $donor = ExternalUser::factory()->create();

    $action = new class extends CreateDonationAction
    {
        protected function validateAthleteRegistration(DonationEvent $donationEvent, int $athleteRegistrationId): AthleteRegistration
        {
            $athleteRegistration = parent::validateAthleteRegistration($donationEvent, $athleteRegistrationId);
            $athleteRegistration->delete();

            return $athleteRegistration;
        }
    };

    $action($event, $donor, [
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 5.00,
        'amount_min' => null,
        'amount_max' => null,
        'comment' => null,
    ]);
})->throws(QueryException::class);

it('formats phone number correctly', function (): void {
    expect(CreateDonationAction::formatPhoneNumber('79 123 45 67', 'CH'))->toBe('+41 79 123 45 67')
        ->and(CreateDonationAction::formatPhoneNumber('151 23456789', 'DE'))->toBe('+49 1512 3456789')
        ->and(CreateDonationAction::formatPhoneNumber('650 1234567', 'AT'))->toBe('+43 650 1234567');
});
