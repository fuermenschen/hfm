<?php

use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Settings\EventSettings;

it('shows side-by-side rankings on large screens and a carousel on small screens', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true, 'title' => 'HoFi 2026']);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $partner = Partner::factory()->create(['name' => 'B Partner']);
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Team Blau']);

    $athlete = ExternalUser::factory()->asAthlete()->create(['first_name' => 'Anna', 'last_name' => 'Ziegler']);
    $registration = AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->create([
        'rounds_done' => 7,
        'partner_id' => $partner->id,
    ]);
    Donation::create([
        'donor_external_user_id' => ExternalUser::factory()->asDonor()->create()->id,
        'athlete_registration_id' => $registration->id,
        'amount_per_round' => 10.0,
        'amount_max' => null,
        'amount_min' => null,
        'comment' => null,
    ]);

    $groupMember = ExternalUser::factory()->asAthlete()->create(['first_name' => 'Beat', 'last_name' => 'Aab']);
    $groupRegistration = AthleteRegistration::factory()->forEvent($event)->forExternalUser($groupMember)->create([
        'rounds_done' => 3,
        'event_group_id' => $group->id,
        'group_membership_status' => GroupMembershipStatus::Accepted,
    ]);
    Donation::create([
        'donor_external_user_id' => ExternalUser::factory()->asDonor()->create()->id,
        'athlete_registration_id' => $groupRegistration->id,
        'amount_per_round' => 5.0,
        'amount_max' => null,
        'amount_min' => null,
        'comment' => null,
    ]);

    $page = visit(route('results'));

    // The carousel slides are always attached to the DOM; their visibility
    // flips with the breakpoint. checkVisibility() sees through attachment.
    $carouselVisibleScript = "document.querySelector('[data-flux-carousel-slide]')?.checkVisibility() ?? false";

    // Large screens (TV): both rankings side by side, carousel hidden.
    // assertNoJavaScriptErrors is intentionally not used here: the page
    // polls every 15 seconds, so the network never settles.
    $page->resize(1920, 1080)
        ->assertSee('Rangliste Sportler:innen')
        ->assertSee('Rangliste Gruppen')
        ->assertSee('Anna Z.')
        ->assertSee('Team Blau');

    expect($page->script($carouselVisibleScript))->toBeFalse();

    // Small screens (phone): auto-playing carousel alternates the rankings.
    $page->resize(390, 844)
        ->assertSee('Anna Z.');

    expect($page->script($carouselVisibleScript))->toBeTrue();
});
