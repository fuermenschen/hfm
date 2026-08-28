<?php

use App\Components\AdminAthleteRegistrationEditor;
use App\Components\AdminPersonTable;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Models\User;
use App\Notifications\PreviousDonorAthleteRegistered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create());
});

it('edits athlete registrations after confirming changed fields while preserving relationships', function (): void {
    $logSpy = Log::spy();

    $event = DonationEvent::factory()->create();
    $externalUser = ExternalUser::factory()->create();
    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $partner = Partner::factory()->create();
    $group = EventGroup::factory()->forEvent($event)->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->forExternalUser($externalUser)->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'event_group_id' => $group->id,
        'rounds_estimated' => 4,
        'rounds_done' => 1,
        'adult' => false,
        'notify_previous_donors' => false,
        'verified' => false,
    ]);

    Livewire::test(AdminAthleteRegistrationEditor::class)
        ->call('open', $registration->id)
        ->set('adult', true)
        ->set('roundsEstimated', 12)
        ->set('roundsDone', 9)
        ->set('comment', 'Aktualisierter Kommentar')
        ->set('notifyPreviousDonors', true)
        ->set('verified', true)
        ->call('save')
        ->assertSet('confirmingSave', true)
        ->assertHasNoErrors()
        ->call('confirmSave')
        ->assertSet('modalOpen', false)
        ->assertHasNoErrors();

    $registration->refresh();

    expect($registration->rounds_estimated)->toBe(12)
        ->and($registration->rounds_done)->toBe(9)
        ->and($registration->verified)->toBeTrue()
        ->and($registration->donation_event_id)->toBe($event->id)
        ->and($registration->external_user_id)->toBe($externalUser->id)
        ->and($registration->sport_type_id)->toBe($sportType->id)
        ->and($registration->partner_id)->toBe($partner->id)
        ->and($registration->event_group_id)->toBe($group->id);

    $logSpy->shouldHaveReceived('info')
        ->with('Admin editor save confirmed.', [
            'editor' => 'AdminAthleteRegistrationEditor',
            'fields' => ['adult', 'roundsEstimated', 'roundsDone', 'comment', 'notifyPreviousDonors', 'verified'],
            'athlete_registration_id' => $registration->id,
        ])
        ->once();
});

it('renders athlete registration editor actions for selected event registrations', function (): void {
    $event = DonationEvent::factory()->create();
    ExternalUser::factory()->asAthlete($event)->create();

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->assertSee('open-athlete-registration-editor', false);
});

it('notifies previous donors when an admin confirms an athlete registration', function (): void {
    Notification::fake();

    $athlete = ExternalUser::factory()->create();
    $previousEvent = DonationEvent::factory()->create();
    $currentEvent = DonationEvent::factory()->create();
    $previousRegistration = AthleteRegistration::factory()->forVerifiedEventUser($previousEvent, $athlete)->create();
    $currentRegistration = AthleteRegistration::factory()->forEvent($currentEvent)->forExternalUser($athlete)->create([
        'verified' => false,
        'notify_previous_donors' => true,
    ]);
    $previousDonor = ExternalUser::factory()->create();

    Donation::factory()->forPair($previousDonor, $previousRegistration)->create();

    Livewire::test(AdminAthleteRegistrationEditor::class)
        ->call('open', $currentRegistration->id)
        ->set('verified', true)
        ->call('save')
        ->assertSet('confirmingSave', true)
        ->call('confirmSave')
        ->assertHasNoErrors();

    Notification::assertSentTo($previousDonor, PreviousDonorAthleteRegistered::class);
});

it('rejects unauthenticated athlete registration editor mutations', function (): void {
    auth('web')->logout();

    Livewire::test(AdminAthleteRegistrationEditor::class)
        ->call('open', 1)
        ->assertForbidden();
});
