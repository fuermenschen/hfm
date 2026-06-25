<?php

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Notifications\PreviousDonorAthleteRegistered;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

it('logs in external user from signed confirmation link and confirms owned registration', function (): void {
    $externalUser = ExternalUser::factory()->create(['first_name' => 'Francesca']);
    $registration = AthleteRegistration::factory()->create([
        'external_user_id' => $externalUser->id,
        'verified' => false,
    ]);

    get(confirmationUrlForTest($externalUser, $registration))
        ->assertRedirect(route('portal.athlete-registration.confirmed'));

    assertAuthenticatedAs($externalUser, 'external');
    expect($registration->refresh()->verified)->toBeTrue();
});

it('keeps confirmation idempotent', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->verified()->create([
        'external_user_id' => $externalUser->id,
    ]);

    get(confirmationUrlForTest($externalUser, $registration))->assertRedirect(route('portal.athlete-registration.confirmed'));

    expect($registration->refresh()->verified)->toBeTrue();
});

it('rejects unsigned confirmation links', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->create([
        'external_user_id' => $externalUser->id,
        'verified' => false,
    ]);

    get(route('portal.athlete-registration.confirm', [
        'uuid' => $externalUser->uuid,
        'athleteRegistration' => $registration,
    ]))->assertForbidden();

    expect($registration->refresh()->verified)->toBeFalse();
});

it('rejects confirmation links for another external users registration', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $otherExternalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->create([
        'external_user_id' => $otherExternalUser->id,
        'verified' => false,
    ]);

    get(confirmationUrlForTest($externalUser, $registration))->assertForbidden();

    expect($registration->refresh()->verified)->toBeFalse();
    assertGuest('external');
});

it('notifies distinct previous donors after first confirmation', function (): void {
    Notification::fake();

    $athlete = ExternalUser::factory()->create(['first_name' => 'Mira', 'last_name' => 'Keller']);
    $previousEvent = DonationEvent::factory()->defaults()->create();
    $otherPreviousEvent = DonationEvent::factory()->defaults()->create();
    $currentEvent = DonationEvent::factory()->defaults()->create();
    $previousRegistration = AthleteRegistration::factory()->forVerifiedEventUser($previousEvent, $athlete)->create();
    $otherPreviousRegistration = AthleteRegistration::factory()->forVerifiedEventUser($otherPreviousEvent, $athlete)->create();
    $currentRegistration = AthleteRegistration::factory()->create([
        'donation_event_id' => $currentEvent->id,
        'external_user_id' => $athlete->id,
        'verified' => false,
        'notify_previous_donors' => true,
    ]);
    $previousDonor = ExternalUser::factory()->create();
    $otherPreviousDonor = ExternalUser::factory()->create();
    $currentEventDonor = ExternalUser::factory()->create();
    $differentAthleteDonor = ExternalUser::factory()->create();

    Donation::factory()->forPair($previousDonor, $previousRegistration)->create();
    Donation::factory()->forPair($previousDonor, $otherPreviousRegistration)->create();
    Donation::factory()->forPair($otherPreviousDonor, $previousRegistration)->create();
    Donation::factory()->forPair($athlete, $previousRegistration)->create();
    Donation::factory()->forPair($currentEventDonor, $currentRegistration)->create();
    Donation::factory()->forDonorExternalUser($differentAthleteDonor)->create([
        'athlete_registration_id' => AthleteRegistration::factory()->forEvent($previousEvent)->create()->id,
    ]);

    get(confirmationUrlForTest($athlete, $currentRegistration))->assertRedirect(route('portal.athlete-registration.confirmed'));

    Notification::assertSentTo($previousDonor, PreviousDonorAthleteRegistered::class);
    Notification::assertSentTo($otherPreviousDonor, PreviousDonorAthleteRegistered::class);
    Notification::assertNotSentTo($athlete, PreviousDonorAthleteRegistered::class);
    Notification::assertNotSentTo($currentEventDonor, PreviousDonorAthleteRegistered::class);
    Notification::assertNotSentTo($differentAthleteDonor, PreviousDonorAthleteRegistered::class);
    Notification::assertSentTimes(PreviousDonorAthleteRegistered::class, 2);

    get(confirmationUrlForTest($athlete, $currentRegistration))->assertRedirect(route('portal.athlete-registration.confirmed'));

    Notification::assertSentTimes(PreviousDonorAthleteRegistered::class, 2);
});

it('does not notify previous donors when athlete opted out', function (): void {
    Notification::fake();

    $athlete = ExternalUser::factory()->create();
    $previousEvent = DonationEvent::factory()->defaults()->create();
    $currentEvent = DonationEvent::factory()->defaults()->create();
    $previousRegistration = AthleteRegistration::factory()->forVerifiedEventUser($previousEvent, $athlete)->create();
    $currentRegistration = AthleteRegistration::factory()->create([
        'donation_event_id' => $currentEvent->id,
        'external_user_id' => $athlete->id,
        'verified' => false,
        'notify_previous_donors' => false,
    ]);
    $previousDonor = ExternalUser::factory()->create();

    Donation::factory()->forPair($previousDonor, $previousRegistration)->create();

    get(confirmationUrlForTest($athlete, $currentRegistration))->assertRedirect(route('portal.athlete-registration.confirmed'));

    Notification::assertNotSentTo($previousDonor, PreviousDonorAthleteRegistered::class);
});

function confirmationUrlForTest(ExternalUser $externalUser, AthleteRegistration $registration): string
{
    return URL::temporarySignedRoute('portal.athlete-registration.confirm', now()->addMinutes(15), [
        'uuid' => $externalUser->uuid,
        'athleteRegistration' => $registration,
    ]);
}
