<?php

use App\Components\Results;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\get;

function resultsTestEvent(array $attributes = []): DonationEvent
{
    $donationEvent = DonationEvent::factory()->create(array_merge([
        'is_published' => true,
    ], $attributes));

    $settings = app(EventSettings::class);
    $settings->current_event_id = $donationEvent->id;
    $settings->save();

    return $donationEvent;
}

function resultsTestRegistration(DonationEvent $event, int $rounds, ?int $partnerId = null): AthleteRegistration
{
    $athlete = ExternalUser::factory()->asAthlete()->create();

    return AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->create([
        'rounds_done' => $rounds,
        'partner_id' => $partnerId,
    ]);
}

function resultsTestDonation(AthleteRegistration $registration, float $perRound, ?int $donorId = null): Donation
{
    return Donation::create([
        'donor_external_user_id' => $donorId ?? ExternalUser::factory()->asDonor()->create()->id,
        'athlete_registration_id' => $registration->id,
        'amount_per_round' => $perRound,
        'amount_max' => null,
        'amount_min' => null,
        'comment' => null,
    ]);
}

it('renders the current event as standalone page', function (): void {
    $event = resultsTestEvent(['title' => 'HöFi 2026']);

    get(route('results'))
        ->assertSuccessful()
        ->assertSeeText('HöFi 2026')
        ->assertSeeText('Live');
});

it('shows an empty state when no current event is configured', function (): void {
    get(route('results'))
        ->assertSuccessful()
        ->assertSeeText('Aktuell ist kein Anlass aktiv.');
});

it('renders successfully and shows per-partner section', function () {
    $event = resultsTestEvent();

    $partner = Partner::factory()->create(['name' => 'Partner X']);
    $registration = resultsTestRegistration($event, 3, $partner->id);
    resultsTestDonation($registration, 10.0);

    Livewire::test(Results::class)
        ->assertStatus(200)
        ->assertSee('Spenden pro Benefizpartner:in')
        ->assertSee('Partner X')
        ->assertDontSee('Einzelresultate');
});

it('ignores data from other events', function (): void {
    $event = resultsTestEvent();
    $oldEvent = DonationEvent::factory()->create();

    $partner = Partner::factory()->create(['name' => 'Partner X']);
    $currentRegistration = resultsTestRegistration($event, 20, $partner->id);
    resultsTestDonation($currentRegistration, 10.0);

    $oldRegistration = resultsTestRegistration($oldEvent, 50, $partner->id);
    resultsTestDonation($oldRegistration, 10.0);

    Livewire::test(Results::class)
        ->assertSet('totals.rounds', 20)
        ->assertSet('totals.athletes', 1)
        ->assertSee('Fr. 200')
        ->assertDontSee('Fr. 700');
});

it('distributes equal-split donations across the event partners', function () {
    $b = Partner::factory()->create(['name' => 'B Partner']);
    $c = Partner::factory()->create(['name' => 'C Partner']);
    $donationEvent = resultsTestEvent(['has_equal_split_option' => true]);

    $registrationEqual = resultsTestRegistration($donationEvent, 10, null); // 10 * 10 = 100
    $registrationB = resultsTestRegistration($donationEvent, 5, $b->id); // 5 * 10 = 50
    $registrationC = resultsTestRegistration($donationEvent, 6, $c->id); // 6 * 5 = 30

    resultsTestDonation($registrationEqual, 10.0);
    resultsTestDonation($registrationB, 10.0);
    resultsTestDonation($registrationC, 5.0);

    Livewire::test(Results::class)
        ->assertStatus(200)
        // Equal-split amount (100) is distributed evenly: B and C get +50 each.
        ->assertSee('B Partner')
        ->assertSee('Fr. 100')
        ->assertSee('C Partner')
        ->assertSee('Fr. 80');
});

it('splits a legacy "alle zu gleichen Teilen" partner across remaining partners', function () {
    $equal = Partner::factory()->create(['name' => 'alle zu gleichen Teilen']);
    $b = Partner::factory()->create(['name' => 'B Partner']);
    $c = Partner::factory()->create(['name' => 'C Partner']);
    $donationEvent = resultsTestEvent();

    $registrationEqual = resultsTestRegistration($donationEvent, 10, $equal->id); // 10 * 10 = 100
    $registrationB = resultsTestRegistration($donationEvent, 5, $b->id); // 5 * 10 = 50
    $registrationC = resultsTestRegistration($donationEvent, 6, $c->id); // 6 * 5 = 30

    resultsTestDonation($registrationEqual, 10.0);
    resultsTestDonation($registrationB, 10.0);
    resultsTestDonation($registrationC, 5.0);

    Livewire::test(Results::class)
        ->assertStatus(200)
        // The legacy partner's 100 splits evenly onto B and C: +50 each.
        ->assertDontSee('alle zu gleichen Teilen')
        ->assertSee('B Partner')
        ->assertSee('Fr. 100')
        ->assertSee('C Partner')
        ->assertSee('Fr. 80');
});

it('does not expose single athlete results anymore', function () {
    $event = resultsTestEvent();
    $partner = Partner::factory()->create(['name' => 'Partner X']);

    $three = ExternalUser::factory()->asAthlete()->create([
        'first_name' => 'Three',
        'last_name' => 'Rounds',
    ]);
    AthleteRegistration::factory()->forEvent($event)->forExternalUser($three)->create([
        'rounds_done' => 3,
        'partner_id' => $partner->id,
    ]);

    Livewire::test(Results::class)
        ->assertStatus(200)
        ->assertDontSee('Einzelresultate')
        ->assertDontSee($three->privacyName());
});

it('counts unique donors via external user identities', function () {
    $event = resultsTestEvent();
    $partner = Partner::factory()->create(['name' => 'Partner X']);

    $registrationOne = resultsTestRegistration($event, 3, $partner->id);
    $registrationTwo = resultsTestRegistration($event, 2, $partner->id);

    $donor = ExternalUser::factory()->asDonor()->create();

    resultsTestDonation($registrationOne, 5.0, $donor->id);
    resultsTestDonation($registrationTwo, 10.0, $donor->id);

    Livewire::test(Results::class)
        ->assertSet('totals.donors', 1);
});

it('recomputes totals on subsequent renders so polling picks up new rounds', function (): void {
    $event = resultsTestEvent();
    $registration = resultsTestRegistration($event, 4);

    Livewire::test(Results::class)
        ->assertSet('totals.rounds', 4);

    $registration->update(['rounds_done' => 9]);

    Livewire::test(Results::class)
        ->assertSet('totals.rounds', 9);
});

it('ranks athletes by actual donations using privacy names', function (): void {
    $event = resultsTestEvent();
    $b = Partner::factory()->create(['name' => 'B Partner']);

    $first = resultsTestRegistration($event, 10, $b->id);
    resultsTestDonation($first, 10.0);
    $second = resultsTestRegistration($event, 4, $b->id);
    resultsTestDonation($second, 10.0);
    $withoutDonations = resultsTestRegistration($event, 99, $b->id);

    Livewire::test(Results::class)
        ->assertSet('totals.athlete_ranking.0.name', $first->externalUser->privacy_name)
        ->assertSet('totals.athlete_ranking.0.amount', 100.0)
        ->assertSet('totals.athlete_ranking.1.name', $second->externalUser->privacy_name)
        ->assertSet('totals.athlete_ranking.1.amount', 40.0)
        ->assertSet('totals.athlete_ranking', fn ($ranking): bool => count($ranking) === 2);
});

it('ranks groups by the donations of their accepted members', function (): void {
    $event = resultsTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Team Blau']);
    $otherGroup = EventGroup::factory()->forEvent($event)->create(['name' => 'Team Rot']);

    $blueCaptain = resultsTestRegistration($event, 10);
    $blueCaptain->update([
        'event_group_id' => $group->id,
        'group_membership_status' => GroupMembershipStatus::Accepted,
    ]);
    resultsTestDonation($blueCaptain, 10.0);

    $rotMember = resultsTestRegistration($event, 4);
    $rotMember->update([
        'event_group_id' => $otherGroup->id,
        'group_membership_status' => GroupMembershipStatus::Accepted,
    ]);
    resultsTestDonation($rotMember, 5.0);

    $pending = resultsTestRegistration($event, 99);
    $pending->update([
        'event_group_id' => $group->id,
        'group_membership_status' => GroupMembershipStatus::Pending,
    ]);
    resultsTestDonation($pending, 50.0);

    Livewire::test(Results::class)
        ->assertSet('totals.group_ranking.0.name', 'Team Blau')
        ->assertSet('totals.group_ranking.0.amount', 100.0)
        ->assertSet('totals.group_ranking.1.name', 'Team Rot')
        ->assertSet('totals.group_ranking.1.amount', 20.0)
        ->assertSet('totals.group_ranking', fn ($ranking): bool => count($ranking) === 2);
});

it('includes min-max clamped amounts in the athlete ranking', function (): void {
    $event = resultsTestEvent();
    $registration = resultsTestRegistration($event, 0);
    $donor = ExternalUser::factory()->asDonor()->create();
    Donation::create([
        'donor_external_user_id' => $donor->id,
        'athlete_registration_id' => $registration->id,
        'amount_per_round' => 1.0,
        'amount_max' => null,
        'amount_min' => 50.0,
        'comment' => null,
    ]);

    Livewire::test(Results::class)
        ->assertSet('totals.athlete_ranking.0.amount', 50.0)
        ->assertSet('totals.athlete_ranking', fn ($ranking): bool => count($ranking) === 1);
});

it('loads totals without per-donation queries', function (): void {
    $event = resultsTestEvent();
    $partner = Partner::factory()->create(['name' => 'Partner X']);

    foreach (range(1, 6) as $index) {
        $registration = resultsTestRegistration($event, 5, $partner->id);
        resultsTestDonation($registration, 10.0);
    }

    $queries = 0;
    DB::listen(fn (): int => $queries++);

    Livewire::test(Results::class);

    // Without the athleteRegistration backfill, DonationService queries once
    // per donation (twice for partnered athletes).
    expect($queries)->toBeLessThan(15);
});

it('limits each ranking to the top ten entries', function (): void {
    $event = resultsTestEvent();
    $partner = Partner::factory()->create(['name' => 'Partner X']);

    foreach (range(1, 11) as $index) {
        $registration = resultsTestRegistration($event, $index, $partner->id);
        resultsTestDonation($registration, 1.0);
    }

    Livewire::test(Results::class)
        ->assertSet('totals.athlete_ranking', fn ($ranking): bool => count($ranking) === 10 && $ranking[0]['amount'] === 11.0);
});

it('refreshes every 15 seconds and supports light and dark appearance', function (): void {
    $event = resultsTestEvent();
    $registration = resultsTestRegistration($event, 4);
    resultsTestDonation($registration, 10.0);

    Livewire::test(Results::class)
        ->assertSee('wire:poll.15s')
        ->assertSee('dark:bg-zinc-900');
});

it('pins the important sections to the viewport with scalable rankings', function (): void {
    $event = resultsTestEvent();
    $registration = resultsTestRegistration($event, 4);
    resultsTestDonation($registration, 10.0);

    Livewire::test(Results::class)
        ->assertSee('h-screen')
        ->assertSee('overflow-hidden')
        ->assertSee('min-h-0 flex-1')
        ->assertDontSee('Sportler:innen</p>')
        ->assertDontSee('Spender:innen</p>');
});
