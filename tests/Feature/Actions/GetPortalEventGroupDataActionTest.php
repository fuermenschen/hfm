<?php

use App\Actions\GetPortalEventGroupDataAction;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use Carbon\Carbon;

it('aggregates confirmed donations for accepted group members', function (): void {
    Carbon::setTestNow('2036-09-01 12:00:00');
    $event = DonationEvent::factory()->year(2036)->create();
    $group = EventGroup::factory()->forEvent($event)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create(['rounds_estimated' => 10, 'rounds_done' => 4]);
    $pendingMember = AthleteRegistration::factory()->pendingGroup($group)->create(['rounds_estimated' => 100]);

    Donation::factory()->forPair(ExternalUser::factory()->create(), $member)->create([
        'amount_per_round' => 2,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => true,
    ]);
    Donation::factory()->forPair(ExternalUser::factory()->create(), $member)->create(['verified' => false]);
    Donation::factory()->forPair(ExternalUser::factory()->create(), $pendingMember)->create([
        'amount_per_round' => 10,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => true,
    ]);

    $data = app(GetPortalEventGroupDataAction::class)($group, $member->externalUser);

    expect($data['accepted'])->toHaveCount(1)
        ->and($data['accepted']->first()->getAttribute('confirmed_donation_count'))->toBe(1)
        ->and($data['accepted']->first()->getAttribute('estimated_donation_amount'))->toBe(20.0)
        ->and($data['groupSummary'])->toMatchArray([
            'confirmedDonationCount' => 1,
            'estimatedAmount' => 20.0,
            'actualAmount' => 8.0,
            'amount' => 20.0,
            'amountLabel' => 'Spenden (geschätzt)',
        ]);

    Carbon::setTestNow();
});

it('uses completed rounds when group event starts', function (): void {
    Carbon::setTestNow('2036-09-12 11:00:00 Europe/Zurich');
    $event = DonationEvent::factory()->year(2036)->create();
    $group = EventGroup::factory()->forEvent($event)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create(['rounds_estimated' => 10, 'rounds_done' => 4]);
    Donation::factory()->forPair(ExternalUser::factory()->create(), $member)->create([
        'amount_per_round' => 2,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => true,
    ]);

    $data = app(GetPortalEventGroupDataAction::class)($group, $member->externalUser);

    expect($data['groupSummary']['amount'])->toBe(8.0)
        ->and($data['groupSummary']['amountLabel'])->toBe('Spenden (tatsächlich)');

    Carbon::setTestNow();
});
