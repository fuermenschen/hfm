<?php

use App\Components\AdminRoundCounter;
use App\Enums\EventState;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create());
});

function roundCounterTestRegistration(DonationEvent $event, string $firstName, string $lastName, array $attributes = []): AthleteRegistration
{
    $user = ExternalUser::factory()->asAthlete()->create([
        'first_name' => $firstName,
        'last_name' => $lastName,
    ]);

    return AthleteRegistration::factory()
        ->forEvent($event)
        ->forExternalUser($user)
        ->create($attributes);
}

it('shows athlete cards with start number, name and rounds', function (): void {
    $event = DonationEvent::factory()->create();
    roundCounterTestRegistration($event, 'Ada', 'Albright', [
        'start_number' => 7,
        'rounds_done' => 3,
        'rounds_estimated' => 19,
        'event_state' => EventState::Running->value,
    ]);

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->assertOk()
        ->assertSee('Ada A.')
        ->assertSee('#7')
        ->assertSee('/19')
        ->assertSee('Alle …')
        ->assertSee('Runden total');
});

it('does not count rounds for finished athletes', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = roundCounterTestRegistration($event, 'Ada', 'Albright', [
        'rounds_done' => 5,
        'event_state' => EventState::Finished->value,
    ]);

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->set('statusFilter', 'all')
        ->call('addRound', $registration->id);

    expect($registration->refresh()->rounds_done)->toBe(5)
        ->and($registration->event_state)->toBe(EventState::Finished);
});

it('hides finished athletes by default and shows them with the finished filter', function (): void {
    $event = DonationEvent::factory()->create();
    roundCounterTestRegistration($event, 'Fini', 'Schluss', [
        'event_state' => EventState::Finished->value,
    ]);
    roundCounterTestRegistration($event, 'Rudi', 'Rennt', [
        'event_state' => EventState::Running->value,
    ]);

    Livewire::withQueryParams(['filter' => 'open'])
        ->test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->assertDontSee('Fini S.')
        ->assertSee('Rudi R.');

    Livewire::withQueryParams(['filter' => 'finished'])
        ->test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->assertSee('Fini S.')
        ->assertDontSee('Rudi R.');
});

it('counts a round and implicitly starts a not-started athlete', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = roundCounterTestRegistration($event, 'Ada', 'Albright', ['rounds_done' => 2]);

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->call('addRound', $registration->id);

    expect($registration->refresh()->rounds_done)->toBe(3)
        ->and($registration->event_state)->toBe(EventState::Running);
});

it('finishes an athlete after confirmation and reactivates a finished one', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = roundCounterTestRegistration($event, 'Ada', 'Albright', [
        'event_state' => EventState::Running->value,
    ]);

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->call('confirmFinish', $registration->id)
        ->assertSet('confirmingFinishId', $registration->id)
        ->call('finish');

    expect($registration->refresh()->event_state)->toBe(EventState::Finished);

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->call('reactivate', $registration->id);

    expect($registration->refresh()->event_state)->toBe(EventState::Running);
});

it('removes a round but stops at zero', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = roundCounterTestRegistration($event, 'Ada', 'Albright', [
        'rounds_done' => 1,
        'event_state' => EventState::Running->value,
    ]);

    $component = Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug]);
    $component->call('removeRound', $registration->id);
    $component->call('removeRound', $registration->id);

    expect($registration->refresh()->rounds_done)->toBe(0);
});

it('starts all athletes together after confirmation', function (): void {
    $event = DonationEvent::factory()->create();
    $first = roundCounterTestRegistration($event, 'Ada', 'Albright');
    $second = roundCounterTestRegistration($event, 'Zora', 'Zimmermann');

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->call('confirmBatch', 'start')
        ->assertSet('confirmingBatch', 'start')
        ->call('runBatch')
        ->assertSet('confirmingBatch', '');

    expect($first->refresh()->event_state)->toBe(EventState::Running)
        ->and($second->refresh()->event_state)->toBe(EventState::Running);
});

it('finishes all athletes of the event after confirmation', function (): void {
    $event = DonationEvent::factory()->create();
    $running = roundCounterTestRegistration($event, 'Rudi', 'Rennt', [
        'event_state' => EventState::Running->value,
    ]);
    $finished = roundCounterTestRegistration($event, 'Fini', 'Schluss', [
        'event_state' => EventState::Finished->value,
    ]);

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->call('confirmBatch', 'finish')
        ->call('runBatch');

    expect($running->refresh()->event_state)->toBe(EventState::Finished)
        ->and($finished->refresh()->event_state)->toBe(EventState::Finished);
});

it('resets all rounds and states of the event after confirmation', function (): void {
    $event = DonationEvent::factory()->create();
    $running = roundCounterTestRegistration($event, 'Rudi', 'Rennt', [
        'rounds_done' => 4,
        'event_state' => EventState::Running->value,
    ]);
    $finished = roundCounterTestRegistration($event, 'Fini', 'Schluss', [
        'rounds_done' => 7,
        'event_state' => EventState::Finished->value,
    ]);

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->call('confirmBatch', 'reset')
        ->call('runBatch');

    expect($running->refresh()->rounds_done)->toBe(0)
        ->and($running->event_state)->toBe(EventState::NotStarted)
        ->and($finished->refresh()->rounds_done)->toBe(0)
        ->and($finished->event_state)->toBe(EventState::NotStarted);
});

it('ignores unknown batch keys', function (): void {
    $event = DonationEvent::factory()->create();

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->call('confirmBatch', 'nope')
        ->assertSet('confirmingBatch', '');
});

it('resets rounds and status of an athlete after confirmation', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = roundCounterTestRegistration($event, 'Ada', 'Albright', [
        'rounds_done' => 5,
        'event_state' => EventState::Finished->value,
    ]);

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->call('confirmReset', $registration->id)
        ->assertSet('confirmingResetId', $registration->id)
        ->call('resetAthlete')
        ->assertSet('confirmingResetId', null);

    expect($registration->refresh()->rounds_done)->toBe(0)
        ->and($registration->event_state)->toBe(EventState::NotStarted);
});

it('follows the event selected in the start numbers tab', function (): void {
    $event = DonationEvent::factory()->create();

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->dispatch('anlass-changed', slug: 'other-event')
        ->assertSet('eventSlug', 'other-event');
});

it('excludes soft-deleted athletes from counts and total rounds', function (): void {
    $event = DonationEvent::factory()->create();
    roundCounterTestRegistration($event, 'Ada', 'Albright', ['rounds_done' => 3]);
    $ghosted = roundCounterTestRegistration($event, 'Zora', 'Zimmermann', ['rounds_done' => 9]);
    $ghosted->externalUser->delete();

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->assertSee('(1)')
        ->assertSet('totalRounds', 3);
});

it('searches by name and start number', function (): void {
    $event = DonationEvent::factory()->create();
    roundCounterTestRegistration($event, 'Ada', 'Albright', ['start_number' => 11]);
    roundCounterTestRegistration($event, 'Zora', 'Zimmermann', ['start_number' => 22]);

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->set('search', 'Albright')
        ->assertSee('Ada A.')
        ->assertDontSee('Zora Z.');

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->set('search', '22')
        ->assertSee('Zora Z.')
        ->assertDontSee('Ada A.');
});

it('forbids write actions for guests', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = roundCounterTestRegistration($event, 'Ada', 'Albright');

    auth()->logout();

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->call('addRound', $registration->id)
        ->assertForbidden();
});

it('ignores registrations of soft-deleted athletes', function (): void {
    $event = DonationEvent::factory()->create();
    $user = ExternalUser::factory()->asAthlete()->create(['first_name' => 'Ghost', 'last_name' => 'Gone']);
    $ghosted = roundCounterTestRegistration($event, 'Ada', 'Albright', ['rounds_done' => 4]);
    $ghosted->externalUser->delete();

    Livewire::test(AdminRoundCounter::class, ['eventSlug' => $event->slug])
        ->assertDontSee('Ada A.')
        ->call('addRound', $ghosted->id);

    expect($ghosted->refresh()->rounds_done)->toBe(4);
});
