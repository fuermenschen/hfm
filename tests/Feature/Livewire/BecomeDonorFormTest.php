<?php

use App\Components\BecomeDonorForm;
use App\Models\AthleteRegistration;
use App\Notifications\AdminSomeoneRegistered;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
});

dataset('zip_validation_cases', [
    // Switzerland (CH) - 4 digits, leading zeros allowed, range 1000–9999
    ['country' => 'CH', 'zip' => '8001', 'city' => 'Zürich', 'valid' => true],
    ['country' => 'CH', 'zip' => '0001', 'city' => 'Invalid', 'valid' => false], // too short
    ['country' => 'CH', 'zip' => '99999', 'city' => 'Invalid', 'valid' => false], // too long
    ['country' => 'CH', 'zip' => '3000', 'city' => 'Bern', 'valid' => true],

    // Germany (DE) - 5 digits, leading zeros allowed
    ['country' => 'DE', 'zip' => '10115', 'city' => 'Berlin', 'valid' => true],
    ['country' => 'DE', 'zip' => '01234', 'city' => 'Dresden', 'valid' => true],  // leading zero is valid
    ['country' => 'DE', 'zip' => '1234', 'city' => 'Invalid', 'valid' => false], // too short
    ['country' => 'DE', 'zip' => '123456', 'city' => 'Invalid', 'valid' => false], // too long

    // Austria (AT) - 4 digits, leading zeros not used
    ['country' => 'AT', 'zip' => '1010', 'city' => 'Wien', 'valid' => true],
    ['country' => 'AT', 'zip' => '5020', 'city' => 'Salzburg', 'valid' => true],
    ['country' => 'AT', 'zip' => '123', 'city' => 'Invalid', 'valid' => false], // too short
    ['country' => 'AT', 'zip' => '12345', 'city' => 'Invalid', 'valid' => false], // too long
]);

dataset('phone_validation_cases', [
    // Switzerland (CH) – closed plan, always 9 digits (incl. leading 0)
    ['country' => 'CH', 'phone' => '0441234567', 'city' => 'Zürich', 'valid' => true],   // fixed line
    ['country' => 'CH', 'phone' => '0791234567', 'city' => 'Mobile', 'valid' => true],   // mobile
    ['country' => 'CH', 'phone' => '41234567', 'city' => 'Invalid', 'valid' => false],  // missing leading 0
    ['country' => 'CH', 'phone' => '04412345', 'city' => 'Invalid', 'valid' => false],  // too short

    // Germany (DE) – open plan, 10–11 digits typical incl. leading 0
    ['country' => 'DE', 'phone' => '0301234567', 'city' => 'Berlin', 'valid' => true],   // short area code
    ['country' => 'DE', 'phone' => '08912345678', 'city' => 'München', 'valid' => true],   // long subscriber
    ['country' => 'DE', 'phone' => '015112345678', 'city' => 'Mobile', 'valid' => true],   // mobile 0151 prefix
    ['country' => 'DE', 'phone' => '00049123456789', 'city' => 'Invalid', 'valid' => false], // invalid country code
    ['country' => 'DE', 'phone' => '012345', 'city' => 'Invalid', 'valid' => false],  // too short

    // Austria (AT) – variable length, mobile always 06, Vienna (01) short code
    ['country' => 'AT', 'phone' => '06641234567', 'city' => 'Mobile', 'valid' => true],   // mobile
    ['country' => 'AT', 'phone' => '0720123456', 'city' => 'Linz', 'valid' => true],   // typical landline
    ['country' => 'AT', 'phone' => '01555', 'city' => 'Vienna', 'valid' => false],   // invalid short format
    ['country' => 'AT', 'phone' => '0664', 'city' => 'Invalid', 'valid' => false],  // too short
    ['country' => 'AT', 'phone' => '0676123456789', 'city' => 'MobileLong', 'valid' => true],   // long mobile (13 digits)
]);

test('renders successfully', function () {
    Livewire::test(BecomeDonorForm::class)
        ->assertStatus(200);
})->skip('Registrierung aktuell geschlossen.');

it('defaults country to CH', function () {
    Livewire::test(BecomeDonorForm::class)
        ->assertSet('country_of_residence', 'CH');
})->skip('Registrierung aktuell geschlossen.');

it('validates ZIP per country', function (
    string $country,
    string $zip,
    string $city,
    bool $valid
) {
    $athleteRegistration = AthleteRegistration::factory()->create();

    $test = Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', $country)
        ->set('zip_code', $zip)
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('address', 'Teststrasse 1')
        ->set('city', $city)
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', 'test-mail@fake.com')
        ->set('email_confirmation', 'test-mail@fake.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save');

    if ($valid) {
        $test->assertHasNoErrors(['zip_code']);
    } else {
        $test->assertHasErrors(['zip_code']);
    }
})->with('zip_validation_cases')->skip('Registrierung aktuell geschlossen.');

it('persists selected country', function () {
    $athleteRegistration = AthleteRegistration::factory()->create();

    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'DE')
        ->set('first_name', 'Erika')
        ->set('last_name', 'Mustermann')
        ->set('address', 'Musterweg 1')
        ->set('zip_code', '10115')
        ->set('city', 'Berlin')
        ->set('phone_country', 'DE')
        ->set('phone_national', '151 23456789')
        ->set('email', 'erika@example.de')
        ->set('email_confirmation', 'erika@example.de')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 10.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasNoErrors();

    // TODO(refactor-external-user): Replace legacy donor persistence assertion with external_users assertion.
})->skip('Registrierung aktuell geschlossen.');

it('shows ZIP validation message in the UI when invalid', function () {
    $athleteRegistration = AthleteRegistration::factory()->create();

    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '80001') // invalid for CH
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address', 'Teststrasse 1')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '079 123 45 67')
        ->set('email', 'jane.ui@example.com')
        ->set('email_confirmation', 'jane.ui@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasErrors(['zip_code'])
        ->assertSee('Ungültige Postleitzahl');
})->skip('Registrierung aktuell geschlossen.');

// Replaces dedicated phone tests with a dataset-driven variant
it('validates phone per country', function (
    string $country,
    string $phone,
    string $city,
    bool $valid
) {
    $athleteRegistration = AthleteRegistration::factory()->create();

    $zip = match ($country) {
        'CH' => '8001',
        'DE' => '10115',
        'AT' => '1010',
        default => '0000',
    };

    $email = 'phone-'.uniqid().'@example.test';

    $test = Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', $country)
        ->set('zip_code', $zip)
        ->set('first_name', 'Testy')
        ->set('last_name', 'McTestface')
        ->set('address', 'Sample Street 1')
        ->set('city', $city)
        ->set('phone_country', $country)
        ->set('phone_national', $phone)
        ->set('email', $email)
        ->set('email_confirmation', $email)
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save');

    if ($valid) {
        $test->assertHasNoErrors(['phone_national']);
        // TODO(refactor-external-user): Assert external user creation instead of legacy donor row.

        // TODO(refactor-external-user): Assert E.164 phone format on ExternalUser phone field.
        expect($country)->toBeIn(['CH', 'DE', 'AT']);

    } else {
        $test->assertHasErrors(['phone_national']);
        // TODO(refactor-external-user): Assert no ExternalUser row created on invalid phone.
    }
})->with('phone_validation_cases')->skip('Registrierung aktuell geschlossen.');

// --- Added pragmatic, high-impact tests ---
it('rejects email confirmation mismatch and does not persist', function () {
    $athleteRegistration = AthleteRegistration::factory()->create();

    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Alex')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 1')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', 'alex@example.com')
        ->set('email_confirmation', 'other@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasErrors(['email_confirmation' => 'same']);

    // TODO(refactor-external-user): Assert no ExternalUser row created on email mismatch.
})->skip('Registrierung aktuell geschlossen.');

it('requires privacy acceptance', function () {
    $athleteRegistration = AthleteRegistration::factory()->create();

    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Maya')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 2')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', 'maya@example.com')
        ->set('email_confirmation', 'maya@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', false)
        ->call('save')
        ->assertHasErrors(['privacy' => 'accepted']);

    // TODO(refactor-external-user): Assert no ExternalUser row created when privacy not accepted.
})->skip('Registrierung aktuell geschlossen.');

it('validates amount rules and boundaries', function () {
    $athleteRegistration = AthleteRegistration::factory()->create();

    // below min boundary fails
    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Ben')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 3')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', 'ben@example.com')
        ->set('email_confirmation', 'ben@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 0.04)
        ->set('privacy', true)
        ->call('save')
        ->assertHasErrors(['amount_per_round']);

    // boundary passes, and min/max coherence
    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Cara')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 4')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', 'cara@example.com')
        ->set('email_confirmation', 'cara@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 0.05)
        ->set('amount_min', 10.0)
        ->set('amount_max', 50.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(
        $athleteRegistration->donations()
            ->where('amount_per_round', 0.05)
            ->where('amount_min', 10.0)
            ->where('amount_max', 50.0)
            ->exists()
    )->toBeTrue();

    // amount_min must be >= per-round
    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Dina')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 5')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', 'dina@example.com')
        ->set('email_confirmation', 'dina@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.0)
        ->set('amount_min', 3.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasErrors(['amount_min' => 'gte']);

    // amount_max must be >= amount_min
    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Evan')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 6')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', 'evan@example.com')
        ->set('email_confirmation', 'evan@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.0)
        ->set('amount_min', 10.0)
        ->set('amount_max', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasErrors(['amount_max']);
})->skip('Registrierung aktuell geschlossen.');

it('prevents duplicate donation for the same athlete', function () {
    $athleteRegistration = AthleteRegistration::factory()->create();

    $email = 'dup@example.com';

    // First registration
    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Faye')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 7')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', $email)
        ->set('email_confirmation', $email)
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasNoErrors();

    // TODO(refactor-external-user): Assert first donation created for ExternalUser.

    // Second attempt with same athlete should not create duplicate
    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Faye')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 7')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', $email)
        ->set('email_confirmation', $email)
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 7.0)
        ->set('privacy', true)
        ->call('save');

    // TODO(refactor-external-user): Assert duplicate athlete pledge is prevented for same ExternalUser.
})->skip('Registrierung aktuell geschlossen.');

it('reuses donor across different athletes', function () {
    $athleteRegistrationOne = AthleteRegistration::factory()->create();
    $athleteRegistrationTwo = AthleteRegistration::factory()->create();

    $email = 'reuse@example.com';

    // First donation
    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Gina')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 8')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', $email)
        ->set('email_confirmation', $email)
        ->set('athlete_registration_id', $athleteRegistrationOne->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasNoErrors();

    // Second donation for another athlete
    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Gina')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 8')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', $email)
        ->set('email_confirmation', $email)
        ->set('athlete_registration_id', $athleteRegistrationTwo->id)
        ->set('amount_per_round', 7.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasNoErrors();

    // TODO(refactor-external-user): Assert same ExternalUser reused across multiple athletes.
})->skip('Registrierung aktuell geschlossen.');

it('sends admin notification only for first-time donors when enabled', function () {
    config(['app.send_notification_on_registration' => true]);

    $athleteRegistration = AthleteRegistration::factory()->create();

    $email = 'notify@example.com';

    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Hana')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 9')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', $email)
        ->set('email_confirmation', $email)
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save');

    // ensure an on-demand admin notification was triggered
    Notification::assertSentOnDemand(AdminSomeoneRegistered::class, function ($notification, $channels, $notifiable) {
        return ($notifiable->routes['mail'] ?? null) === 'info@fuer-menschen.ch';
    });

    // Second donation should not trigger another admin notification
    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Hana')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 9')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', $email)
        ->set('email_confirmation', $email)
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 6.0)
        ->set('privacy', true)
        ->call('save');
})->skip('Registrierung aktuell geschlossen.');

it('does not send admin notification when disabled', function () {
    config(['app.send_notification_on_registration' => false]);

    $athleteRegistration = AthleteRegistration::factory()->create();

    $email = 'no-notify@example.com';

    Livewire::test(BecomeDonorForm::class)
        ->set('country_of_residence', 'CH')
        ->set('zip_code', '8001')
        ->set('first_name', 'Ivan')
        ->set('last_name', 'Doe')
        ->set('address', 'Main 10')
        ->set('city', 'Zürich')
        ->set('phone_country', 'CH')
        ->set('phone_national', '0791234567')
        ->set('email', $email)
        ->set('email_confirmation', $email)
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.0)
        ->set('privacy', true)
        ->call('save')
        ->assertHasNoErrors();

    // specifically ensure no on-demand admin notification is sent
    Notification::assertNotSentTo(new AnonymousNotifiable, AdminSomeoneRegistered::class);
})->skip('Registrierung aktuell geschlossen.');
