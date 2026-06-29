<?php

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\User;
use App\Notifications\PreviousDonorAthleteRegistered;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('logs in external user from signed confirmation link without confirming registration', function (): void {
    $externalUser = ExternalUser::factory()->create(['first_name' => 'Francesca']);
    $registration = AthleteRegistration::factory()->create([
        'external_user_id' => $externalUser->id,
        'verified' => false,
    ]);

    get(confirmationUrlForTest($externalUser))
        ->assertRedirect(route('portal.dashboard'));

    assertAuthenticatedAs($externalUser, 'external');
    expect($registration->refresh()->verified)->toBeFalse();
});

it('logs out admin session from signed confirmation link', function (): void {
    $user = User::factory()->create();
    $externalUser = ExternalUser::factory()->create();

    actingAs($user, 'web');

    get(confirmationUrlForTest($externalUser))
        ->assertRedirect(route('portal.dashboard'));

    assertGuest('web');
    assertAuthenticatedAs($externalUser, 'external');
});

it('confirms owned registration from authenticated portal action', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->create([
        'external_user_id' => $externalUser->id,
        'verified' => false,
    ]);

    actingAs($externalUser, 'external');

    post(route('portal.athlete-registration.confirm.perform', $registration))
        ->assertRedirect(route('portal.athlete-registration.confirmed'));

    expect($registration->refresh()->verified)->toBeTrue();
});

it('keeps confirmation idempotent', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->verified()->create([
        'external_user_id' => $externalUser->id,
    ]);

    actingAs($externalUser, 'external');

    post(route('portal.athlete-registration.confirm.perform', $registration))->assertRedirect(route('portal.athlete-registration.confirmed'));

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
    ]))->assertForbidden();

    expect($registration->refresh()->verified)->toBeFalse();
});

it('does not confirm another external users registration from signed login link', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $otherExternalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->create([
        'external_user_id' => $otherExternalUser->id,
        'verified' => false,
    ]);

    get(confirmationUrlForTest($externalUser))->assertRedirect(route('portal.dashboard'));

    expect($registration->refresh()->verified)->toBeFalse();
    assertAuthenticatedAs($externalUser, 'external');
});

it('rejects portal confirmation for another external users registration', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $otherExternalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->create([
        'external_user_id' => $otherExternalUser->id,
        'verified' => false,
    ]);

    actingAs($externalUser, 'external');

    post(route('portal.athlete-registration.confirm.perform', $registration))->assertForbidden();

    expect($registration->refresh()->verified)->toBeFalse();
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

    actingAs($athlete, 'external');

    post(route('portal.athlete-registration.confirm.perform', $currentRegistration))->assertRedirect(route('portal.athlete-registration.confirmed'));

    Notification::assertSentTo($previousDonor, PreviousDonorAthleteRegistered::class);
    Notification::assertSentTo($otherPreviousDonor, PreviousDonorAthleteRegistered::class);
    Notification::assertNotSentTo($athlete, PreviousDonorAthleteRegistered::class);
    Notification::assertNotSentTo($currentEventDonor, PreviousDonorAthleteRegistered::class);
    Notification::assertNotSentTo($differentAthleteDonor, PreviousDonorAthleteRegistered::class);
    Notification::assertSentTimes(PreviousDonorAthleteRegistered::class, 2);

    post(route('portal.athlete-registration.confirm.perform', $currentRegistration))->assertRedirect(route('portal.athlete-registration.confirmed'));

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

    actingAs($athlete, 'external');

    post(route('portal.athlete-registration.confirm.perform', $currentRegistration))->assertRedirect(route('portal.athlete-registration.confirmed'));

    Notification::assertNotSentTo($previousDonor, PreviousDonorAthleteRegistered::class);
});

function confirmationUrlForTest(ExternalUser $externalUser): string
{
    return URL::temporarySignedRoute('portal.athlete-registration.confirm', now()->addMinutes(15), [
        'uuid' => $externalUser->uuid,
    ]);
}
