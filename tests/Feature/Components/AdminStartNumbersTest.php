<?php

use App\Actions\AssignStartNumbersAction;
use App\Actions\SetStartNumberAction;
use App\Components\AdminStartNumbers;
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

function startNumbersTestRegistration(DonationEvent $event, string $firstName, string $lastName, ?int $startNumber = null): AthleteRegistration
{
    $user = ExternalUser::factory()->asAthlete()->create([
        'first_name' => $firstName,
        'last_name' => $lastName,
    ]);

    return AthleteRegistration::factory()
        ->forEvent($event)
        ->forExternalUser($user)
        ->when($startNumber !== null, fn ($factory) => $factory->withStartNumber($startNumber))
        ->create();
}

it('renders the registrations of the selected event', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = startNumbersTestRegistration($event, 'Ada', 'Albright');

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->assertOk()
        ->assertSee('Ada A.')
        ->assertSee('Startnummer');
});

it('assigns the next free start number to a single registration', function (): void {
    $event = DonationEvent::factory()->create();
    startNumbersTestRegistration($event, 'Ada', 'Albright', 5);
    $registration = startNumbersTestRegistration($event, 'Zora', 'Zimmermann');

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('assignNextNumber', $registration->id);

    expect($registration->refresh()->start_number)->toBe(6);
});

it('clears a start number', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = startNumbersTestRegistration($event, 'Ada', 'Albright', 5);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('clearNumber', $registration->id);

    expect($registration->refresh()->start_number)->toBeNull();
});

it('assigns only missing registrations, skipping taken numbers', function (): void {
    $event = DonationEvent::factory()->create();
    $taken = startNumbersTestRegistration($event, 'Ada', 'Albright', 2);
    $missing = startNumbersTestRegistration($event, 'Zora', 'Zimmermann');

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->set('firstNumber', 1)
        ->call('assignMissing');

    expect($missing->refresh()->start_number)->toBe(1)
        ->and($taken->refresh()->start_number)->toBe(2);
});

it('re-assigns all registrations alphabetically', function (): void {
    $event = DonationEvent::factory()->create();
    $zora = startNumbersTestRegistration($event, 'Zora', 'Zimmermann', 99);
    $ada = startNumbersTestRegistration($event, 'Ada', 'Albright', 1);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->set('firstNumber', 10)
        ->call('assignAll');

    expect($ada->refresh()->start_number)->toBe(10)
        ->and($zora->refresh()->start_number)->toBe(11);
});

it('keeps the typed first number when confirming the assign-all modal', function (): void {
    $event = DonationEvent::factory()->create();
    $ada = startNumbersTestRegistration($event, 'Ada', 'Albright');

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->set('firstNumber', 100)
        ->call('confirmAssignAll')
        ->assertSet('firstNumber', 100)
        ->call('assignAll');

    expect($ada->refresh()->start_number)->toBe(100);
});

it('shows and sorts by done rounds', function (): void {
    $event = DonationEvent::factory()->create();
    startNumbersTestRegistration($event, 'Ada', 'Albright', 1)->update(['rounds_done' => 2, 'rounds_estimated' => 10]);
    startNumbersTestRegistration($event, 'Zora', 'Zimmermann', 2)->update(['rounds_done' => 9, 'rounds_estimated' => 5]);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->assertSeeInOrder(['2', '9'])
        ->assertSee('10')
        ->assertSee('5');

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->set('sortField', 'rounds_done')
        ->assertSeeInOrder(['9', '2']);
});

it('sorts by start number and by name', function (): void {
    $event = DonationEvent::factory()->create();
    startNumbersTestRegistration($event, 'Ada', 'Albright', 1);
    startNumbersTestRegistration($event, 'Zora', 'Zimmermann', 2);
    startNumbersTestRegistration($event, 'Bernd', 'Berg', 3);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->assertSeeInOrder(['Ada A.', 'Bernd B.', 'Zora Z.']);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->set('sortField', 'start_number')
        ->assertSeeInOrder(['Ada A.', 'Zora Z.', 'Bernd B.']);
});

it('exports the start list as csv', function (): void {
    $event = DonationEvent::factory()->create();
    startNumbersTestRegistration($event, 'Ada', 'Albright', 1);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('exportAll', 'csv')
        ->assertFileDownloaded();
});

it('forbids write actions for guests', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = startNumbersTestRegistration($event, 'Ada', 'Albright');

    auth()->logout();

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('assignNextNumber', $registration->id)
        ->assertForbidden();
});

it('sets a manual start number for a single registration', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = startNumbersTestRegistration($event, 'Ada', 'Albright', 5);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('openNumberEditor', $registration->id)
        ->assertSet('numberInput', 5)
        ->set('numberInput', 42)
        ->call('setNumber');

    expect($registration->refresh()->start_number)->toBe(42);
});

it('rejects a manual start number that is taken', function (): void {
    $event = DonationEvent::factory()->create();
    $taken = startNumbersTestRegistration($event, 'Ada', 'Albright', 7);
    $registration = startNumbersTestRegistration($event, 'Zora', 'Zimmermann');

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('openNumberEditor', $registration->id)
        ->set('numberInput', 7)
        ->call('setNumber');

    expect($registration->refresh()->start_number)->toBeNull()
        ->and($taken->refresh()->start_number)->toBe(7);
});

it('validates the manual start number input', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = startNumbersTestRegistration($event, 'Ada', 'Albright');

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('openNumberEditor', $registration->id)
        ->set('numberInput', 0)
        ->call('setNumber')
        ->assertHasErrors(['numberInput']);

    expect($registration->refresh()->start_number)->toBeNull();
});

it('sets manual rounds for a single registration', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = startNumbersTestRegistration($event, 'Ada', 'Albright');

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('openRoundsEditor', $registration->id)
        ->assertSet('roundsInput', $registration->rounds_done)
        ->set('roundsInput', 12)
        ->call('setRounds');

    expect($registration->refresh()->rounds_done)->toBe(12);
});

it('sets the status of a registration and ignores unknown values', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = startNumbersTestRegistration($event, 'Ada', 'Albright');

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('setStatus', $registration->id, 'running');

    expect($registration->refresh()->event_state)->toBe(EventState::Running);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('setStatus', $registration->id, 'nope');

    expect($registration->refresh()->event_state)->toBe(EventState::Running);
});

it('clears all start numbers of the event after confirmation', function (): void {
    $event = DonationEvent::factory()->create();
    $numbered = startNumbersTestRegistration($event, 'Ada', 'Albright', 3);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('confirmClearAll')
        ->call('clearAllNumbers');

    expect($numbered->refresh()->start_number)->toBeNull();
});

it('announces event changes so the round counter tab follows', function (): void {
    $event = DonationEvent::factory()->create();
    startNumbersTestRegistration($event, 'Ada', 'Albright');

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->set('eventSlug', 'other-event')
        ->assertDispatched('anlass-changed', slug: 'other-event');
});

it('always shows all columns regardless of stale visibility sessions', function (): void {
    $event = DonationEvent::factory()->create();
    startNumbersTestRegistration($event, 'Ada', 'Albright', 1);

    $user = User::query()->first();
    session()->put('datatable.visible_columns.'.$user->id.'.'.AdminStartNumbers::class, ['start_number', 'first_name', 'last_name', 'event_state']);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->assertSee('Startnummer')
        ->assertSee('Öffentliche ID')
        ->assertSee('Runden')
        ->assertSee('Geschätzt')
        ->assertSee('Status');
});

it('ignores registrations of soft-deleted athletes', function (): void {
    $event = DonationEvent::factory()->create();
    $user = ExternalUser::factory()->asAthlete()->create(['first_name' => 'Ghost', 'last_name' => 'Gone']);
    $ghosted = AthleteRegistration::factory()->forEvent($event)->forExternalUser($user)->create();

    $user->delete();

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->assertDontSee('Ghost G.');

    resolve(AssignStartNumbersAction::class)($event, 1, false);

    expect($ghosted->refresh()->start_number)->toBeNull();
});

it('survives a lost uniqueness race with a warning instead of an error', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = startNumbersTestRegistration($event, 'Ada', 'Albright');
    $winner = startNumbersTestRegistration($event, 'Zora', 'Zimmermann');

    $mock = mock(SetStartNumberAction::class);
    $mock->shouldReceive('__invoke')
        ->andThrow(new InvalidArgumentException('Die Startnummer 1 wurde zwischenzeitlich vergeben. Bitte erneut versuchen.'));
    app()->instance(SetStartNumberAction::class, $mock);

    Livewire::test(AdminStartNumbers::class, ['eventSlug' => $event->slug])
        ->call('assignNextNumber', $registration->id);

    expect($registration->refresh()->start_number)->toBeNull()
        ->and($winner->refresh()->start_number)->toBeNull();
});
