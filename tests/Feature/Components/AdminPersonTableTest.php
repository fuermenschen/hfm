<?php

use App\Components\AdminPersonTable;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\User;
use App\Settings\EventSettings;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

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

it('shows selected partner and public ID for athletes', function (): void {
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create(['name' => 'Test Partner']);
    $athlete = ExternalUser::factory()->asAthlete($event, [
        'partner_id' => $partner->id,
    ])->create([
        'public_id' => '4WUFNB',
    ]);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->assertSee('Benefizpartner:in')
        ->assertSee('Test Partner')
        ->assertDontSee('4WU-FNB')
        ->call('toggleColumn', 'public_id_string')
        ->assertSee('4WU-FNB');
});

it('shows equal split for athletes without a selected partner', function (): void {
    $event = DonationEvent::factory()->create();
    $athlete = ExternalUser::factory()->asAthlete($event, [
        'partner_id' => null,
    ])->create();

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->assertSee(__('app.equal_split_full'));
});

it('searches athletes by public ID and selected partner', function (): void {
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create(['name' => 'Search Partner']);
    $matchingAthlete = ExternalUser::factory()->asAthlete($event, ['partner_id' => $partner->id])->create([
        'first_name' => 'Matching Athlete',
        'public_id' => '4WUFNB',
    ]);
    $otherAthlete = ExternalUser::factory()->asAthlete($event)->create(['first_name' => 'Other Athlete']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->set('search', '4WUFNB')
        ->assertSee($matchingAthlete->first_name)
        ->assertDontSee($otherAthlete->first_name)
        ->set('search', 'Search Partner')
        ->assertSee($matchingAthlete->first_name)
        ->assertDontSee($otherAthlete->first_name);
});

it('sorts athletes by selected partner', function (): void {
    $event = DonationEvent::factory()->create();
    $alphaPartner = Partner::factory()->create(['name' => 'Alpha Partner']);
    $betaPartner = Partner::factory()->create(['name' => 'Beta Partner']);
    ExternalUser::factory()->asAthlete($event, ['partner_id' => $betaPartner->id])->create(['first_name' => 'Beta Athlete']);
    ExternalUser::factory()->asAthlete($event, ['partner_id' => $alphaPartner->id])->create(['first_name' => 'Alpha Athlete']);

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->call('sortBy', 'partner')
        ->assertSeeInOrder(['Alpha Partner', 'Beta Partner']);
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

it('explains why athlete documents require one selected event', function (): void {
    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', '')
        ->assertSee('Für Dokumente bitte genau einen Anlass auswählen.')
        ->assertSee('Willkommensbrief')
        ->assertSee('Personalisierter Flyer')
        ->assertSee('Alle Sportler:innen')
        ->assertSee('Ausgewählte Sportler:innen')
        ->assertSee('Dokumente werden erstellt...');
});

it('downloads a flyer for an athlete in the selected event', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create([
        'first_name' => 'Peter',
        'last_name' => 'Muster',
        'public_id' => '4WUFNB',
    ]);

    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->call('downloadAthleteDocument', $athlete->id, 'personalized-flyer')
        ->assertFileDownloaded('2026_Peter_M_4WU-FNB_Personalisierter_Flyer.pdf');
});

it('downloads selected athlete flyers as an event-scoped archive', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->set('checkboxValues', [$athlete->id])
        ->call('downloadSelectedAthleteDocuments', 'personalized-flyer')
        ->assertFileDownloaded('2026_Personalisierte_Flyer.zip');
});

it('downloads all athlete flyers for the selected event', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    ExternalUser::factory()->asAthlete($event)->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', $event->slug)
        ->call('downloadAllAthleteDocuments', 'personalized-flyer')
        ->assertFileDownloaded('2026_Personalisierte_Flyer.zip');
});

it('does not download athlete documents without a selected event', function (): void {
    $athlete = ExternalUser::factory()->asAthlete()->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->set('eventSlug', '')
        ->call('downloadAthleteDocument', $athlete->id, 'personalized-flyer')
        ->assertNoFileDownloaded();
});

it('does not start a second athlete document archive while one is running', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();

    actingAs(User::factory()->create());
    $lock = Cache::lock('admin-athlete-document-download:'.auth()->id(), 600);
    expect($lock->get())->toBeTrue();

    try {
        Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
            ->set('eventSlug', $event->slug)
            ->set('checkboxValues', [$athlete->id])
            ->call('downloadSelectedAthleteDocuments', 'personalized-flyer')
            ->assertNoFileDownloaded();
    } finally {
        $lock->release();
    }
});

it('does not start a single athlete document while another is running', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->asAthlete($event)->create();

    actingAs(User::factory()->create());
    $lock = Cache::lock('admin-athlete-document-download:'.auth()->id(), 600);
    expect($lock->get())->toBeTrue();

    try {
        Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
            ->set('eventSlug', $event->slug)
            ->call('downloadAthleteDocument', $athlete->id, 'personalized-flyer')
            ->assertNoFileDownloaded();
    } finally {
        $lock->release();
    }
});
