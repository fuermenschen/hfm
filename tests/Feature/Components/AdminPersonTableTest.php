<?php

use App\Components\AdminPersonTable;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Settings\EventSettings;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('renders athlete and donor tables with their role labels', function (string $role, string $label): void {
    Livewire::test(AdminPersonTable::class, ['role' => $role])
        ->assertSee('Ausgewählt: 0')
        ->assertSee($label);
})->with([
    'athletes' => ['athlete', 'Sportler:innen'],
    'donors' => ['donor', 'Spender:innen'],
]);

it('shows only people matching the selected role', function (): void {
    $athlete = ExternalUser::factory()->asAthlete()->create(['first_name' => 'Athlete Only']);
    $donor = ExternalUser::factory()->asDonor()->create(['first_name' => 'Donor Only']);
    $both = ExternalUser::factory()->asAthlete()->asDonor()->create(['first_name' => 'Both Roles']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->assertSee($athlete->first_name)
        ->assertSee($both->first_name)
        ->assertDontSee($donor->first_name);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->assertSee($donor->first_name)
        ->assertSee($both->first_name)
        ->assertDontSee($athlete->first_name);
});

it('searches with query builder like clauses', function (): void {
    ExternalUser::factory()->asAthlete()->create(['first_name' => 'Alpha']);
    ExternalUser::factory()->asAthlete()->create(['first_name' => 'Control']);

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('search', 'Alpha')
        ->assertSee('Alpha')
        ->assertDontSee('Control');

    expect(collect($queries)->contains(
        fn (string $query): bool => str_contains($query, 'like ?'),
    ))->toBeTrue()
        ->and(collect($queries)->contains(
            fn (string $query): bool => str_contains($query, ' escape '),
        ))->toBeFalse();
});

it('filters unique athletes by event and shows their linked events', function (): void {
    $event2025 = DonationEvent::factory()->year(2025)->create();
    $event2026 = DonationEvent::factory()->year(2026)->create();
    $bothEvents = ExternalUser::factory()
        ->asAthlete($event2025)
        ->asAthlete($event2026)
        ->create(['first_name' => 'Both Events']);
    $only2025 = ExternalUser::factory()->asAthlete($event2025)->create(['first_name' => 'Only 2025']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->assertSee($bothEvents->first_name)
        ->assertSee('2025')
        ->assertSee('2026')
        ->set('eventSlug', $event2026->slug)
        ->assertSee($bothEvents->first_name)
        ->assertDontSee($only2025->first_name);
});

it('filters donors through the athlete registration event', function (): void {
    $event2025 = DonationEvent::factory()->year(2025)->create();
    $event2026 = DonationEvent::factory()->year(2026)->create();
    $donor2025 = ExternalUser::factory()->asDonor($event2025)->create(['first_name' => 'Donor 2025']);
    $donor2026 = ExternalUser::factory()->asDonor($event2026)->create(['first_name' => 'Donor 2026']);

    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])
        ->set('eventSlug', $event2026->slug)
        ->assertSee($donor2026->first_name)
        ->assertDontSee($donor2025->first_name);
});

it('clears stale selection when an event filter changes', function (): void {
    $event = DonationEvent::factory()->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('checkboxValues', [$athlete->id])
        ->set('eventSlug', $event->slug)
        ->assertSet('checkboxValues', []);
});

it('returns no people for an invalid event filter', function (): void {
    $athlete = ExternalUser::factory()->asAthlete()->create(['first_name' => 'Visible Athlete']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', 'invalid')
        ->assertDontSee($athlete->first_name)
        ->assertSee('Keine Sportler:innen für diesen Anlass vorhanden.');
});

it('shows all people again when the event filter is cleared', function (): void {
    $event = DonationEvent::factory()->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create(['first_name' => 'Visible Athlete']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->set('eventSlug', null)
        ->assertSee($athlete->first_name);
});

it('defaults athlete and donor tables to the current event', function (string $role): void {
    $currentEvent = DonationEvent::factory()->year(2026)->create(['is_published' => true]);
    $otherEvent = DonationEvent::factory()->year(2025)->create(['is_published' => true]);

    $currentPerson = ExternalUser::factory()
        ->{$role === 'athlete' ? 'asAthlete' : 'asDonor'}($currentEvent)
        ->create(['first_name' => 'Current Person']);
    $otherPerson = ExternalUser::factory()
        ->{$role === 'athlete' ? 'asAthlete' : 'asDonor'}($otherEvent)
        ->create(['first_name' => 'Other Person']);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $currentEvent->id;
    $settings->save();

    Livewire::test(AdminPersonTable::class, ['role' => $role])
        ->assertSet('eventSlug', $currentEvent->slug)
        ->assertSee($currentPerson->first_name)
        ->assertDontSee($otherPerson->first_name);
})->with(['athlete', 'donor']);

it('keeps an explicit event filter instead of the current event', function (): void {
    $currentEvent = DonationEvent::factory()->year(2026)->create(['is_published' => true]);
    $otherEvent = DonationEvent::factory()->year(2025)->create(['is_published' => true]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $currentEvent->id;
    $settings->save();

    Livewire::withQueryParams(['anlass' => $otherEvent->slug])
        ->test(AdminPersonTable::class, ['role' => 'athlete'])
        ->assertSet('eventSlug', $otherEvent->slug);
});

it('shows all people when the event filter is explicitly empty', function (): void {
    $currentEvent = DonationEvent::factory()->year(2026)->create(['is_published' => true]);
    ExternalUser::factory()->asAthlete($currentEvent)->create(['first_name' => 'Visible Athlete']);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $currentEvent->id;
    $settings->save();

    Livewire::withQueryParams(['anlass' => ''])
        ->test(AdminPersonTable::class, ['role' => 'athlete'])
        ->assertSet('eventSlug', '')
        ->assertSee('Visible Athlete');
});
