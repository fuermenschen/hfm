<?php

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Services\DonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Common dataset for both estimated and actual calculations
// rounds, perRound, min, max, expected total

dataset('amount_cases', [
    ['rounds' => 10, 'perRound' => 5.0, 'min' => null, 'max' => null, 'expected' => 50.00],
    ['rounds' => 1, 'perRound' => 2.0, 'min' => 10.0, 'max' => null, 'expected' => 10.00],
    ['rounds' => 20, 'perRound' => 5.0, 'min' => null, 'max' => 60.0, 'expected' => 60.00],
    // Below min then above max (covered via dataset values)
]);

// Group: calculateEstimatedAmount

describe('calculateEstimatedAmount', function () {
    it('computes estimated amount with min/max caps applied', function (int $rounds, float $perRound, ?float $min, ?float $max, float $expected): void {
        $service = app(DonationService::class);
        $athleteRegistration = new AthleteRegistration(['rounds_estimated' => $rounds]);
        $donation = new Donation([
            'amount_per_round' => $perRound,
            'amount_min' => $min,
            'amount_max' => $max,
        ]);
        $donation->setRelation('athleteRegistration', $athleteRegistration);

        expect($service->calculateEstimatedAmount($donation))->toBe(round($expected, 2));
    })->with('amount_cases');

    it('respects both min and max changing with athlete rounds', function (): void {
        $service = app(DonationService::class);
        $athleteRegistration = new AthleteRegistration(['rounds_estimated' => 5]);
        $donation = new Donation([
            'amount_per_round' => 10.0, // subtotal = 50
            'amount_min' => 60.0,
            'amount_max' => 80.0,
        ]);
        $donation->setRelation('athleteRegistration', $athleteRegistration);

        expect($service->calculateEstimatedAmount($donation))->toBe(60.00);

        $athleteRegistration->rounds_estimated = 20; // subtotal = 200 -> capped at max
        expect($service->calculateEstimatedAmount($donation))->toBe(80.00);
    });
});

// Group: calculateActualAmount

describe('calculateActualAmount', function () {
    it('computes actual amount with min/max caps applied', function (int $rounds, float $perRound, ?float $min, ?float $max, float $expected): void {
        $service = app(DonationService::class);
        $athleteRegistration = new AthleteRegistration(['rounds_done' => $rounds]);
        $donation = new Donation([
            'amount_per_round' => $perRound,
            'amount_min' => $min,
            'amount_max' => $max,
        ]);
        $donation->setRelation('athleteRegistration', $athleteRegistration);

        expect($service->calculateActualAmount($donation))->toBe(round($expected, 2));
    })->with('amount_cases');
});

// DB-backed aggregate methods

describe('calculateEstimatedTotal', function () {
    it('sums estimated amounts across all donations in the DB', function (): void {
        $service = app(DonationService::class);
        $event = DonationEvent::factory()->create();
        $sport = SportType::create(['name' => 'Run']);
        $partner = Partner::create(['name' => 'P1']);
        $donor1 = ExternalUser::factory()->create(['email' => 'd1@example.com']);
        $donor2 = ExternalUser::factory()->create(['email' => 'd2@example.com']);
        $donor3 = ExternalUser::factory()->create(['email' => 'd3@example.com']);

        $a1 = ExternalUser::factory()->create(['email' => 'a1@example.com']);
        $a2 = ExternalUser::factory()->create(['email' => 'a2@example.com']);
        $a3 = ExternalUser::factory()->create(['email' => 'a3@example.com']);

        $r1 = AthleteRegistration::factory()->create([
            'external_user_id' => $a1->id,
            'donation_event_id' => $event->id,
            'sport_type_id' => $sport->id,
            'partner_id' => $partner->id,
            'rounds_estimated' => 10,
        ]);
        $r2 = AthleteRegistration::factory()->create([
            'external_user_id' => $a2->id,
            'donation_event_id' => $event->id,
            'sport_type_id' => $sport->id,
            'partner_id' => $partner->id,
            'rounds_estimated' => 5,
        ]);
        $r3 = AthleteRegistration::factory()->create([
            'external_user_id' => $a3->id,
            'donation_event_id' => $event->id,
            'sport_type_id' => $sport->id,
            'partner_id' => $partner->id,
            'rounds_estimated' => 50,
        ]);

        // Donations with caps to exercise min/max
        Donation::create(['donor_external_user_id' => $donor1->id, 'athlete_registration_id' => $r1->id, 'amount_per_round' => 2.0, 'amount_min' => null, 'amount_max' => null, 'comment' => null]); // 10*2=20
        Donation::create(['donor_external_user_id' => $donor2->id, 'athlete_registration_id' => $r2->id, 'amount_per_round' => 3.0, 'amount_min' => 20.0, 'amount_max' => null, 'comment' => null]); // 5*3=15 -> 20 (min)
        Donation::create(['donor_external_user_id' => $donor3->id, 'athlete_registration_id' => $r3->id, 'amount_per_round' => 1.0, 'amount_min' => null, 'amount_max' => 30.0, 'comment' => null]); // 50*1=50 -> 30 (max)

        $donations = Donation::query()->with('athleteRegistration')->get();
        $total = $service->calculateEstimatedTotal($donations);

        expect($total)->toBe(70.00);
    });
});

describe('calculateActualTotal', function () {
    it('sums actual amounts across all donations in the DB', function (): void {
        $service = app(DonationService::class);
        $event = DonationEvent::factory()->create();
        $sport = SportType::create(['name' => 'Run']);
        $partner = Partner::create(['name' => 'P1']);
        $d1 = ExternalUser::factory()->create(['email' => 'da1@example.com']);
        $d2 = ExternalUser::factory()->create(['email' => 'da2@example.com']);

        $a1 = ExternalUser::factory()->create(['email' => 'aa1@example.com']);
        $a2 = ExternalUser::factory()->create(['email' => 'aa2@example.com']);

        $r1 = AthleteRegistration::factory()->create([
            'external_user_id' => $a1->id,
            'donation_event_id' => $event->id,
            'sport_type_id' => $sport->id,
            'partner_id' => $partner->id,
            'rounds_done' => 12,
        ]);
        $r2 = AthleteRegistration::factory()->create([
            'external_user_id' => $a2->id,
            'donation_event_id' => $event->id,
            'sport_type_id' => $sport->id,
            'partner_id' => $partner->id,
            'rounds_done' => 1,
        ]);

        Donation::create(['donor_external_user_id' => $d1->id, 'athlete_registration_id' => $r1->id, 'amount_per_round' => 2.0, 'amount_min' => null, 'amount_max' => 30.0, 'comment' => null]); // 12*2=24
        Donation::create(['donor_external_user_id' => $d2->id, 'athlete_registration_id' => $r2->id, 'amount_per_round' => 1.0, 'amount_min' => 10.0, 'amount_max' => null, 'comment' => null]); // 1*1=1 -> 10 (min)

        $donations = Donation::query()->with('athleteRegistration')->get();
        $total = $service->calculateActualTotal($donations);

        expect($total)->toBe(34.00);
    });
});

describe('calculateEstimatedTotalPerPartner', function () {
    it('groups estimated totals by partner id', function (): void {
        $service = app(DonationService::class);
        $event = DonationEvent::factory()->create();
        $sport = SportType::create(['name' => 'Run']);
        $p1 = Partner::create(['name' => 'Partner 1']);
        $p2 = Partner::create(['name' => 'Partner 2']);
        $d1 = ExternalUser::factory()->create(['email' => 'p1d1@example.com']);
        $d2 = ExternalUser::factory()->create(['email' => 'p1d2@example.com']);
        $d3 = ExternalUser::factory()->create(['email' => 'p2d1@example.com']);

        $a1 = ExternalUser::factory()->create(['email' => 'pa1@example.com']);
        $a2 = ExternalUser::factory()->create(['email' => 'pa2@example.com']);
        $b1 = ExternalUser::factory()->create(['email' => 'pb1@example.com']);

        $r1 = AthleteRegistration::factory()->create(['external_user_id' => $a1->id, 'donation_event_id' => $event->id, 'sport_type_id' => $sport->id, 'partner_id' => $p1->id, 'rounds_estimated' => 10]);
        $r2 = AthleteRegistration::factory()->create(['external_user_id' => $a2->id, 'donation_event_id' => $event->id, 'sport_type_id' => $sport->id, 'partner_id' => $p1->id, 'rounds_estimated' => 5]);
        $r3 = AthleteRegistration::factory()->create(['external_user_id' => $b1->id, 'donation_event_id' => $event->id, 'sport_type_id' => $sport->id, 'partner_id' => $p2->id, 'rounds_estimated' => 3]);

        Donation::create(['donor_external_user_id' => $d1->id, 'athlete_registration_id' => $r1->id, 'amount_per_round' => 2.0, 'amount_min' => null, 'amount_max' => null, 'comment' => null]); // 20
        Donation::create(['donor_external_user_id' => $d2->id, 'athlete_registration_id' => $r2->id, 'amount_per_round' => 10.0, 'amount_min' => null, 'amount_max' => 40.0, 'comment' => null]); // 5*10=50 -> 40
        Donation::create(['donor_external_user_id' => $d3->id, 'athlete_registration_id' => $r3->id, 'amount_per_round' => 10.0, 'amount_min' => 40.0, 'amount_max' => null, 'comment' => null]); // 3*10=30 -> 40

        $donations = Donation::query()->with('athleteRegistration.partner')->get();
        $totals = $service->calculateEstimatedTotalPerPartner($donations);

        expect($totals)->toBe([
            $p1->id => 60.00,
            $p2->id => 40.00,
        ]);
    });
});

describe('calculateActualTotalPerPartner', function () {
    it('groups actual totals by partner id', function (): void {
        $service = app(DonationService::class);
        $event = DonationEvent::factory()->create();
        $sport = SportType::create(['name' => 'Run']);
        $p1 = Partner::create(['name' => 'Partner 1']);
        $p2 = Partner::create(['name' => 'Partner 2']);
        $d1 = ExternalUser::factory()->create(['email' => 'ap1d1@example.com']);
        $d2 = ExternalUser::factory()->create(['email' => 'ap1d2@example.com']);
        $d3 = ExternalUser::factory()->create(['email' => 'ap2d1@example.com']);

        $a1 = ExternalUser::factory()->create(['email' => 'aaap1@example.com']);
        $a2 = ExternalUser::factory()->create(['email' => 'aaap2@example.com']);
        $b1 = ExternalUser::factory()->create(['email' => 'aaapb1@example.com']);

        $r1 = AthleteRegistration::factory()->create(['external_user_id' => $a1->id, 'donation_event_id' => $event->id, 'sport_type_id' => $sport->id, 'partner_id' => $p1->id, 'rounds_done' => 12]);
        $r2 = AthleteRegistration::factory()->create(['external_user_id' => $a2->id, 'donation_event_id' => $event->id, 'sport_type_id' => $sport->id, 'partner_id' => $p1->id, 'rounds_done' => 1]);
        $r3 = AthleteRegistration::factory()->create(['external_user_id' => $b1->id, 'donation_event_id' => $event->id, 'sport_type_id' => $sport->id, 'partner_id' => $p2->id, 'rounds_done' => 100]);

        Donation::create(['donor_external_user_id' => $d1->id, 'athlete_registration_id' => $r1->id, 'amount_per_round' => 2.0, 'amount_min' => null, 'amount_max' => 30.0, 'comment' => null]); // 12*2=24
        Donation::create(['donor_external_user_id' => $d2->id, 'athlete_registration_id' => $r2->id, 'amount_per_round' => 1.0, 'amount_min' => 10.0, 'amount_max' => null, 'comment' => null]); // 1*1=1 -> 10
        Donation::create(['donor_external_user_id' => $d3->id, 'athlete_registration_id' => $r3->id, 'amount_per_round' => 0.5, 'amount_min' => null, 'amount_max' => 40.0, 'comment' => null]); // 100*0.5=50 -> 40

        $donations = Donation::query()->with('athleteRegistration.partner')->get();
        $totals = $service->calculateActualTotalPerPartner($donations);

        expect($totals)->toBe([
            $p1->id => 34.00,
            $p2->id => 40.00,
        ]);
    });
});
