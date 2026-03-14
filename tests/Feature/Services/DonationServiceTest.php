<?php

use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Partner;
use App\Models\SportType;
use App\Services\DonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

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
    beforeEach(function (): void {
        $this->service = app(DonationService::class);
    });

    it('computes estimated amount with min/max caps applied', function (int $rounds, float $perRound, ?float $min, ?float $max, float $expected): void {
        $athlete = new Athlete(['rounds_estimated' => $rounds]);
        $donation = new Donation([
            'amount_per_round' => $perRound,
            'amount_min' => $min,
            'amount_max' => $max,
        ]);
        $donation->setRelation('athlete', $athlete);

        expect($this->service->calculateEstimatedAmount($donation))->toBe(round($expected, 2));
    })->with('amount_cases');

    it('respects both min and max changing with athlete rounds', function (): void {
        $athlete = new Athlete(['rounds_estimated' => 5]);
        $donation = new Donation([
            'amount_per_round' => 10.0, // subtotal = 50
            'amount_min' => 60.0,
            'amount_max' => 80.0,
        ]);
        $donation->setRelation('athlete', $athlete);

        expect($this->service->calculateEstimatedAmount($donation))->toBe(60.00);

        $athlete->rounds_estimated = 20; // subtotal = 200 -> capped at max
        expect($this->service->calculateEstimatedAmount($donation))->toBe(80.00);
    });
});

// Group: calculateActualAmount

describe('calculateActualAmount', function () {
    beforeEach(function (): void {
        $this->service = app(DonationService::class);
    });

    it('computes actual amount with min/max caps applied', function (int $rounds, float $perRound, ?float $min, ?float $max, float $expected): void {
        $athlete = new Athlete;
        $athlete->rounds_done = $rounds;
        $donation = new Donation([
            'amount_per_round' => $perRound,
            'amount_min' => $min,
            'amount_max' => $max,
        ]);
        $donation->setRelation('athlete', $athlete);

        expect($this->service->calculateActualAmount($donation))->toBe(round($expected, 2));
    })->with('amount_cases');
});

// Group: calculateEstimatedTotalForAthlete

describe('calculateEstimatedTotalForAthlete', function () {
    beforeEach(function (): void {
        $this->service = app(DonationService::class);
    });

    it('sums estimated amounts across preloaded donations without hitting DB', function (): void {
        $athlete = new Athlete([
            'rounds_estimated' => 10,
        ]);

        $donation1 = new Donation([
            'amount_per_round' => 2.0, // 10 * 2 = 20
            'amount_min' => null,
            'amount_max' => null,
        ]);
        $donation2 = new Donation([
            'amount_per_round' => 5.0, // 10 * 5 = 50 (within 30..70)
            'amount_min' => 30.0,
            'amount_max' => 70.0,
        ]);

        // Preload relation to avoid DB inside the service
        $athlete->setRelation('donations', collect([$donation1, $donation2]));

        $total = $this->service->calculateEstimatedTotalForAthlete($athlete);

        expect($total)->toBe(70.00);
    });
});

// Group: calculateActualTotalForAthlete

describe('calculateActualTotalForAthlete', function () {
    beforeEach(function (): void {
        $this->service = app(DonationService::class);
    });

    it('sums actual amounts across preloaded donations without hitting DB', function (): void {
        $athlete = new Athlete;
        $athlete->rounds_done = 12;

        $donation1 = new Donation([
            'amount_per_round' => 2.0, // 12 * 2 = 24
            'amount_min' => null,
            'amount_max' => 30.0, // cap not hit
        ]);
        $donation2 = new Donation([
            'amount_per_round' => 1.0, // 12 * 1 = 12 -> min 30 applies
            'amount_min' => 30.0,
            'amount_max' => null,
        ]);

        $athlete->setRelation('donations', collect([$donation1, $donation2]));

        $total = $this->service->calculateActualTotalForAthlete($athlete);

        expect($total)->toBe(54.00);
    });
});

// DB-backed aggregate methods

describe('calculateEstimatedTotal', function () {
    beforeEach(function (): void {
        $this->service = app(DonationService::class);
        Notification::fake();

        // Minimal required records
        $this->sport = SportType::create(['name' => 'Run']);
        $this->partner = Partner::create(['name' => 'P1']);
    });

    it('sums estimated amounts across all donations in the DB', function (): void {
        // Create donors
        $donor1 = Donor::create([
            'first_name' => 'Dan', 'last_name' => 'One', 'address' => 'Addr',
            'zip_code' => 1000, 'city' => 'City', 'country_of_residence' => 'CH',
            'phone_number' => '000', 'email' => 'd1@example.com',
        ]);
        $donor2 = Donor::create([
            'first_name' => 'Deb', 'last_name' => 'Two', 'address' => 'Addr',
            'zip_code' => 1000, 'city' => 'City', 'country_of_residence' => 'CH',
            'phone_number' => '000', 'email' => 'd2@example.com',
        ]);
        $donor3 = Donor::create([
            'first_name' => 'Dom', 'last_name' => 'Three', 'address' => 'Addr',
            'zip_code' => 1000, 'city' => 'City', 'country_of_residence' => 'CH',
            'phone_number' => '000', 'email' => 'd3@example.com',
        ]);

        // Athletes
        $a1 = Athlete::create([
            'first_name' => 'A', 'last_name' => 'One', 'address' => 'X', 'zip_code' => 1000,
            'city' => 'City', 'phone_number' => '0', 'email' => 'a1@example.com', 'adult' => 1,
            'sport_type_id' => $this->sport->id, 'partner_id' => $this->partner->id, 'rounds_estimated' => 10,
        ]);
        $a2 = Athlete::create([
            'first_name' => 'B', 'last_name' => 'Two', 'address' => 'X', 'zip_code' => 1000,
            'city' => 'City', 'phone_number' => '0', 'email' => 'a2@example.com', 'adult' => 1,
            'sport_type_id' => $this->sport->id, 'partner_id' => $this->partner->id, 'rounds_estimated' => 5,
        ]);
        $a3 = Athlete::create([
            'first_name' => 'C', 'last_name' => 'Three', 'address' => 'X', 'zip_code' => 1000,
            'city' => 'City', 'phone_number' => '0', 'email' => 'a3@example.com', 'adult' => 1,
            'sport_type_id' => $this->sport->id, 'partner_id' => $this->partner->id, 'rounds_estimated' => 50,
        ]);

        // Donations with caps to exercise min/max
        Donation::create(['donator_id' => $donor1->id, 'athlete_id' => $a1->id, 'amount_per_round' => 2.0, 'amount_min' => null, 'amount_max' => null, 'comment' => null]); // 10*2=20
        Donation::create(['donator_id' => $donor2->id, 'athlete_id' => $a2->id, 'amount_per_round' => 3.0, 'amount_min' => 20.0, 'amount_max' => null, 'comment' => null]); // 5*3=15 -> 20 (min)
        Donation::create(['donator_id' => $donor3->id, 'athlete_id' => $a3->id, 'amount_per_round' => 1.0, 'amount_min' => null, 'amount_max' => 30.0, 'comment' => null]); // 50*1=50 -> 30 (max)

        $donations = Donation::query()->with('athlete')->get();
        $total = $this->service->calculateEstimatedTotal($donations);

        expect($total)->toBe(70.00);
    });
});

describe('calculateActualTotal', function () {
    beforeEach(function (): void {
        $this->service = app(DonationService::class);
        Notification::fake();

        $this->sport = SportType::create(['name' => 'Run']);
        $this->partner = Partner::create(['name' => 'P1']);
    });

    it('sums actual amounts across all donations in the DB', function (): void {
        $d1 = Donor::create([
            'first_name' => 'Don', 'last_name' => ' One', 'address' => 'Addr',
            'zip_code' => 1000, 'city' => 'City', 'country_of_residence' => 'CH',
            'phone_number' => '000', 'email' => 'da1@example.com',
        ]);
        $d2 = Donor::create([
            'first_name' => 'Don', 'last_name' => ' Two', 'address' => 'Addr',
            'zip_code' => 1000, 'city' => 'City', 'country_of_residence' => 'CH',
            'phone_number' => '000', 'email' => 'da2@example.com',
        ]);

        $a1 = Athlete::create([
            'first_name' => 'AA', 'last_name' => 'One', 'address' => 'X', 'zip_code' => 1000,
            'city' => 'City', 'phone_number' => '0', 'email' => 'aa1@example.com', 'adult' => 1,
            'sport_type_id' => $this->sport->id, 'partner_id' => $this->partner->id, 'rounds_estimated' => 0,
        ]);
        $a1->rounds_done = 12; // persisted below
        $a1->save();

        $a2 = Athlete::create([
            'first_name' => 'BB', 'last_name' => 'Two', 'address' => 'X', 'zip_code' => 1000,
            'city' => 'City', 'phone_number' => '0', 'email' => 'aa2@example.com', 'adult' => 1,
            'sport_type_id' => $this->sport->id, 'partner_id' => $this->partner->id, 'rounds_estimated' => 0,
        ]);
        $a2->rounds_done = 1;
        $a2->save();

        Donation::create(['donator_id' => $d1->id, 'athlete_id' => $a1->id, 'amount_per_round' => 2.0, 'amount_min' => null, 'amount_max' => 30.0, 'comment' => null]); // 12*2=24
        Donation::create(['donator_id' => $d2->id, 'athlete_id' => $a2->id, 'amount_per_round' => 1.0, 'amount_min' => 10.0, 'amount_max' => null, 'comment' => null]); // 1*1=1 -> 10 (min)

        $donations = Donation::query()->with('athlete')->get();
        $total = $this->service->calculateActualTotal($donations);

        expect($total)->toBe(34.00);
    });
});

describe('calculateEstimatedTotalPerPartner', function () {
    beforeEach(function (): void {
        $this->service = app(DonationService::class);
        Notification::fake();
        $this->sport = SportType::create(['name' => 'Run']);
        $this->p1 = Partner::create(['name' => 'Partner 1']);
        $this->p2 = Partner::create(['name' => 'Partner 2']);
    });

    it('groups estimated totals by partner id', function (): void {
        $d1 = Donor::create(['first_name' => 'D', 'last_name' => '1', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'country_of_residence' => 'CH', 'phone_number' => '0', 'email' => 'p1d1@example.com']);
        $d2 = Donor::create(['first_name' => 'D', 'last_name' => '2', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'country_of_residence' => 'CH', 'phone_number' => '0', 'email' => 'p1d2@example.com']);
        $d3 = Donor::create(['first_name' => 'D', 'last_name' => '3', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'country_of_residence' => 'CH', 'phone_number' => '0', 'email' => 'p2d1@example.com']);

        $a1 = Athlete::create(['first_name' => 'A', 'last_name' => '1', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'phone_number' => '0', 'email' => 'pa1@example.com', 'adult' => 1, 'sport_type_id' => $this->sport->id, 'partner_id' => $this->p1->id, 'rounds_estimated' => 10]);
        $a2 = Athlete::create(['first_name' => 'A', 'last_name' => '2', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'phone_number' => '0', 'email' => 'pa2@example.com', 'adult' => 1, 'sport_type_id' => $this->sport->id, 'partner_id' => $this->p1->id, 'rounds_estimated' => 5]);
        $b1 = Athlete::create(['first_name' => 'B', 'last_name' => '1', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'phone_number' => '0', 'email' => 'pb1@example.com', 'adult' => 1, 'sport_type_id' => $this->sport->id, 'partner_id' => $this->p2->id, 'rounds_estimated' => 3]);

        Donation::create(['donator_id' => $d1->id, 'athlete_id' => $a1->id, 'amount_per_round' => 2.0, 'amount_min' => null, 'amount_max' => null, 'comment' => null]); // 20
        Donation::create(['donator_id' => $d2->id, 'athlete_id' => $a2->id, 'amount_per_round' => 10.0, 'amount_min' => null, 'amount_max' => 40.0, 'comment' => null]); // 5*10=50 -> 40
        Donation::create(['donator_id' => $d3->id, 'athlete_id' => $b1->id, 'amount_per_round' => 10.0, 'amount_min' => 40.0, 'amount_max' => null, 'comment' => null]); // 3*10=30 -> 40

        $donations = Donation::query()->with('athlete.partner')->get();
        $totals = $this->service->calculateEstimatedTotalPerPartner($donations);

        expect($totals)->toBe([
            $this->p1->id => 60.00,
            $this->p2->id => 40.00,
        ]);
    });
});

describe('calculateActualTotalPerPartner', function () {
    beforeEach(function (): void {
        $this->service = app(DonationService::class);
        Notification::fake();
        $this->sport = SportType::create(['name' => 'Run']);
        $this->p1 = Partner::create(['name' => 'Partner 1']);
        $this->p2 = Partner::create(['name' => 'Partner 2']);
    });

    it('groups actual totals by partner id', function (): void {
        $d1 = Donor::create(['first_name' => 'D', 'last_name' => '1', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'country_of_residence' => 'CH', 'phone_number' => '0', 'email' => 'ap1d1@example.com']);
        $d2 = Donor::create(['first_name' => 'D', 'last_name' => '2', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'country_of_residence' => 'CH', 'phone_number' => '0', 'email' => 'ap1d2@example.com']);
        $d3 = Donor::create(['first_name' => 'D', 'last_name' => '3', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'country_of_residence' => 'CH', 'phone_number' => '0', 'email' => 'ap2d1@example.com']);

        $a1 = Athlete::create(['first_name' => 'A', 'last_name' => '1', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'phone_number' => '0', 'email' => 'aaap1@example.com', 'adult' => 1, 'sport_type_id' => $this->sport->id, 'partner_id' => $this->p1->id, 'rounds_estimated' => 0]);
        $a1->rounds_done = 12;
        $a1->save();
        $a2 = Athlete::create(['first_name' => 'A', 'last_name' => '2', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'phone_number' => '0', 'email' => 'aaap2@example.com', 'adult' => 1, 'sport_type_id' => $this->sport->id, 'partner_id' => $this->p1->id, 'rounds_estimated' => 0]);
        $a2->rounds_done = 1;
        $a2->save();
        $b1 = Athlete::create(['first_name' => 'B', 'last_name' => '1', 'address' => 'A', 'zip_code' => 1, 'city' => 'C', 'phone_number' => '0', 'email' => 'aaapb1@example.com', 'adult' => 1, 'sport_type_id' => $this->sport->id, 'partner_id' => $this->p2->id, 'rounds_estimated' => 0]);
        $b1->rounds_done = 100;
        $b1->save();

        Donation::create(['donator_id' => $d1->id, 'athlete_id' => $a1->id, 'amount_per_round' => 2.0, 'amount_min' => null, 'amount_max' => 30.0, 'comment' => null]); // 12*2=24
        Donation::create(['donator_id' => $d2->id, 'athlete_id' => $a2->id, 'amount_per_round' => 1.0, 'amount_min' => 10.0, 'amount_max' => null, 'comment' => null]); // 1*1=1 -> 10
        Donation::create(['donator_id' => $d3->id, 'athlete_id' => $b1->id, 'amount_per_round' => 0.5, 'amount_min' => null, 'amount_max' => 40.0, 'comment' => null]); // 100*0.5=50 -> 40

        $donations = Donation::query()->with('athlete.partner')->get();
        $totals = $this->service->calculateActualTotalPerPartner($donations);

        expect($totals)->toBe([
            $this->p1->id => 34.00,
            $this->p2->id => 40.00,
        ]);
    });
});
