<?php

use App\Console\Commands\BackfillExternalUsersCommand;
use App\Models\Athlete;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\Donor;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\Console\Tester\CommandTester;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->event = DonationEvent::factory()->create();
    $this->partner = Partner::factory()->create();
    $this->sportType = SportType::query()->create([
        'name' => 'Laufen',
    ]);
});

describe('preflight blockers', function () {
    it('fails on blocker and performs no writes in write mode', function (Closure $seedBlocker, string $expectedOutput): void {
        seedHappyPath($this);
        $seedBlocker($this);

        $this->artisan('hfm:backfill:external-users')
            ->expectsOutputToContain($expectedOutput)
            ->assertFailed();

        assertNoNewSchemaWrites();
    })->with([
        'duplicate normalized donor emails' => [
            function (): void {
                Donor::factory()->create(['email' => 'Person@example.com']);
                Donor::factory()->create(['email' => ' person@example.com ']);
            },
            'Duplicate normalized donor emails found.',
        ],
        'duplicate normalized athlete emails' => [
            function (object $testCase): void {
                Athlete::factory()->create([
                    'donation_event_id' => $testCase->event->id,
                    'partner_id' => $testCase->partner->id,
                    'sport_type_id' => $testCase->sportType->id,
                    'email' => 'Athlete@example.com',
                ]);

                Athlete::factory()->create([
                    'donation_event_id' => $testCase->event->id,
                    'partner_id' => $testCase->partner->id,
                    'sport_type_id' => $testCase->sportType->id,
                    'email' => ' athlete@example.com ',
                ]);
            },
            'Duplicate normalized athlete emails found.',
        ],
        'duplicate legacy donation pairs' => [
            function (object $testCase): void {
                $athlete = Athlete::factory()->create([
                    'donation_event_id' => $testCase->event->id,
                    'partner_id' => $testCase->partner->id,
                    'sport_type_id' => $testCase->sportType->id,
                ]);
                $donor = Donor::factory()->create();

                Donation::query()->create([
                    'donor_id' => $donor->id,
                    'athlete_id' => $athlete->id,
                    'amount_per_round' => 5,
                    'amount_max' => 50,
                    'amount_min' => 10,
                    'comment' => null,
                    'verified' => false,
                ]);

                Donation::query()->create([
                    'donor_id' => $donor->id,
                    'athlete_id' => $athlete->id,
                    'amount_per_round' => 7,
                    'amount_max' => 60,
                    'amount_min' => 10,
                    'comment' => null,
                    'verified' => false,
                ]);
            },
            'Duplicate legacy donation pairs (donor_id, athlete_id) found.',
        ],
        'athletes missing donation event' => [
            function (object $testCase): void {
                Athlete::factory()->create([
                    'donation_event_id' => null,
                    'partner_id' => $testCase->partner->id,
                    'sport_type_id' => $testCase->sportType->id,
                ]);
            },
            'Athletes without donation_event_id found.',
        ],
        'donors with blank normalized email' => [
            function (): void {
                Donor::factory()->create(['email' => '   ']);
            },
            'Donors with blank normalized emails found.',
        ],
        'athletes with blank normalized email' => [
            function (object $testCase): void {
                Athlete::factory()->create([
                    'donation_event_id' => $testCase->event->id,
                    'partner_id' => $testCase->partner->id,
                    'sport_type_id' => $testCase->sportType->id,
                    'email' => '   ',
                ]);
            },
            'Athletes with blank normalized emails found.',
        ],
    ]);

    it('fails in dry-run and write mode when target schema not empty', function (): void {
        seedHappyPath($this);

        ExternalUser::factory()->create();

        $this->artisan('hfm:backfill:external-users --dry-run')
            ->expectsOutputToContain('Target tables/columns are not empty.')
            ->assertFailed();

        $this->artisan('hfm:backfill:external-users')
            ->expectsOutputToContain('Target tables/columns are not empty.')
            ->assertFailed();
    });
});

describe('dry-run contract', function () {
    it('passes preflight prints preview and performs no writes', function (): void {
        Athlete::factory()->create([
            'donation_event_id' => $this->event->id,
            'partner_id' => $this->partner->id,
            'sport_type_id' => $this->sportType->id,
            'email' => 'preview-athlete@example.com',
        ]);

        Donor::factory()->create([
            'email' => 'preview-donor@example.com',
        ]);

        $this->artisan('hfm:backfill:external-users --dry-run')
            ->expectsOutputToContain('Preflight passed. Safe to write.')
            ->expectsOutputToContain('Dry run: write phase not executed.')
            ->expectsOutputToContain('Dry-run merge preview:')
            ->expectsOutputToContain('preview-athlete@example.com => athlete:')
            ->expectsOutputToContain('preview-donor@example.com => athlete:- donor:')
            ->assertSuccessful();

        assertNoNewSchemaWrites();
    });
});

describe('write mode contract', function () {
    it('backfills external users athlete registrations and donation mappings', function (): void {
        $athlete = Athlete::factory()->create([
            'donation_event_id' => $this->event->id,
            'partner_id' => $this->partner->id,
            'sport_type_id' => $this->sportType->id,
            'email' => 'athlete@example.com',
        ]);

        $donor = Donor::factory()->create([
            'email' => 'donor@example.com',
        ]);

        $donation = Donation::query()->create([
            'donor_id' => $donor->id,
            'athlete_id' => $athlete->id,
            'amount_per_round' => 12,
            'amount_max' => 120,
            'amount_min' => 0,
            'comment' => 'Go!',
            'verified' => true,
        ]);

        $this->artisan('hfm:backfill:external-users')
            ->expectsOutputToContain('Backfill write completed successfully.')
            ->assertSuccessful();

        expect(ExternalUser::query()->count())->toBe(2);

        $athleteExternal = ExternalUser::query()->where('legacy_athlete_id', $athlete->id)->first();
        $donorExternal = ExternalUser::query()->where('legacy_donor_id', $donor->id)->first();

        expect($athleteExternal)->not->toBeNull();
        expect($donorExternal)->not->toBeNull();

        $registration = AthleteRegistration::query()
            ->where('external_user_id', $athleteExternal->id)
            ->first();

        expect($registration)->not->toBeNull();
        expect($registration->donation_event_id)->toBe($this->event->id);

        $donation->refresh();
        expect($donation->donor_external_user_id)->toBe($donorExternal->id);
        expect($donation->athlete_registration_id)->toBe($registration->id);
    });

    it('write mode is non-rerunnable and fails when target schema already filled', function (): void {
        seedHappyPath($this);

        $this->artisan('hfm:backfill:external-users')->assertSuccessful();

        $this->artisan('hfm:backfill:external-users')
            ->expectsOutputToContain('Target tables/columns are not empty.')
            ->assertFailed();
    });

    it('rolls back all writes when post-write parity validation fails', function (): void {
        seedHappyPath($this);

        $command = new class extends BackfillExternalUsersCommand
        {
            protected function validatePostWriteParity(): void
            {
                throw new RuntimeException('Parity check failed for rollback test.');
            }
        };

        $command->setLaravel($this->app);
        $tester = new CommandTester($command);

        expect(fn (): int => $tester->execute([]))
            ->toThrow(RuntimeException::class, 'Parity check failed for rollback test.');

        assertNoNewSchemaWrites();
    });
});

describe('identity mapping rules', function () {
    it('merges dual-role user by normalized email and prefers athlete profile fields', function (): void {
        $athlete = Athlete::factory()->create([
            'donation_event_id' => $this->event->id,
            'partner_id' => $this->partner->id,
            'sport_type_id' => $this->sportType->id,
            'email' => 'DualRole@example.com',
            'first_name' => 'AthleteFirst',
            'last_name' => 'AthleteLast',
            'address' => 'Athlete Street 1',
            'zip_code' => 8400,
            'city' => 'Winterthur',
            'phone_number' => '+41 11 111 11 11',
        ]);

        $donor = Donor::factory()->create([
            'email' => ' dualrole@example.com ',
            'first_name' => 'DonorFirst',
            'last_name' => 'DonorLast',
            'address' => 'Donor Street 2',
            'zip_code' => '9999',
            'city' => 'Zurich',
            'phone_number' => '+41 22 222 22 22',
            'country_of_residence' => 'DE',
        ]);

        $this->artisan('hfm:backfill:external-users')->assertSuccessful();

        expect(ExternalUser::query()->count())->toBe(1);

        $externalUser = ExternalUser::query()->firstOrFail();

        expect($externalUser->legacy_athlete_id)->toBe($athlete->id)
            ->and($externalUser->legacy_donor_id)->toBe($donor->id)
            ->and($externalUser->email)->toBe('dualrole@example.com')
            ->and($externalUser->first_name)->toBe('AthleteFirst')
            ->and($externalUser->last_name)->toBe('AthleteLast')
            ->and($externalUser->address)->toBe('Athlete Street 1')
            ->and($externalUser->zip_code)->toBe('8400')
            ->and($externalUser->city)->toBe('Winterthur')
            ->and($externalUser->phone_number)->toBe('+41 11 111 11 11')
            ->and($externalUser->country_of_residence)->toBe('DE')
            ->and(Str::length($externalUser->public_id))->toBe(6);
    });

    it('fills missing athlete profile fields from donor data for dual-role users', function (): void {
        Athlete::factory()->create([
            'donation_event_id' => $this->event->id,
            'partner_id' => $this->partner->id,
            'sport_type_id' => $this->sportType->id,
            'email' => 'fill@example.com',
            'first_name' => 'AthleteFirst',
            'last_name' => 'AthleteLast',
            'address' => '',
            'city' => '',
            'phone_number' => '',
        ]);

        Donor::factory()->create([
            'email' => ' fill@example.com ',
            'address' => 'Donor Address 3',
            'zip_code' => '7777',
            'city' => 'Bern',
            'phone_number' => '+41 33 333 33 33',
            'country_of_residence' => 'AT',
        ]);

        $this->artisan('hfm:backfill:external-users')->assertSuccessful();

        $externalUser = ExternalUser::query()->firstOrFail();

        expect($externalUser->first_name)->toBe('AthleteFirst')
            ->and($externalUser->last_name)->toBe('AthleteLast')
            ->and($externalUser->address)->toBe('Donor Address 3')
            ->and($externalUser->city)->toBe('Bern')
            ->and($externalUser->phone_number)->toBe('+41 33 333 33 33')
            ->and($externalUser->country_of_residence)->toBe('AT');
    });

    it('creates independent external users for unmatched athlete-only and donor-only rows', function (): void {
        $athleteOnly = Athlete::factory()->create([
            'donation_event_id' => $this->event->id,
            'partner_id' => $this->partner->id,
            'sport_type_id' => $this->sportType->id,
            'email' => 'athlete-only@example.com',
        ]);

        $donorOnly = Donor::factory()->create([
            'email' => 'donor-only@example.com',
        ]);

        $this->artisan('hfm:backfill:external-users')->assertSuccessful();

        $athleteExternal = ExternalUser::query()->where('legacy_athlete_id', $athleteOnly->id)->first();
        $donorExternal = ExternalUser::query()->where('legacy_donor_id', $donorOnly->id)->first();

        expect($athleteExternal)->not->toBeNull()
            ->and($athleteExternal->legacy_donor_id)->toBeNull()
            ->and($athleteExternal->country_of_residence)->toBe('CH')
            ->and($donorExternal)->not->toBeNull()
            ->and($donorExternal->legacy_athlete_id)->toBeNull();
    });
});

describe('post-write integrity criteria', function () {
    it('preserves grouped amount parity by event and donor', function (): void {
        $eventA = DonationEvent::factory()->create();
        $eventB = DonationEvent::factory()->create();

        $athleteA = Athlete::factory()->create([
            'donation_event_id' => $eventA->id,
            'partner_id' => $this->partner->id,
            'sport_type_id' => $this->sportType->id,
            'email' => 'a-athlete@example.com',
        ]);

        $athleteB = Athlete::factory()->create([
            'donation_event_id' => $eventB->id,
            'partner_id' => $this->partner->id,
            'sport_type_id' => $this->sportType->id,
            'email' => 'b-athlete@example.com',
        ]);

        $donor = Donor::factory()->create(['email' => 'parity-donor@example.com']);

        Donation::query()->create([
            'donor_id' => $donor->id,
            'athlete_id' => $athleteA->id,
            'amount_per_round' => 10,
            'amount_max' => 100,
            'amount_min' => 0,
            'verified' => false,
        ]);

        Donation::query()->create([
            'donor_id' => $donor->id,
            'athlete_id' => $athleteB->id,
            'amount_per_round' => 12,
            'amount_max' => 120,
            'amount_min' => 0,
            'verified' => false,
        ]);

        $this->artisan('hfm:backfill:external-users')->assertSuccessful();

        $legacyByEvent = Donation::query()
            ->join('athletes', 'athletes.id', '=', 'donations.athlete_id')
            ->selectRaw('athletes.donation_event_id as event_id, SUM(donations.amount_per_round) as total')
            ->groupBy('athletes.donation_event_id')
            ->pluck('total', 'event_id');

        $newByEvent = Donation::query()
            ->join('athlete_registrations', 'athlete_registrations.id', '=', 'donations.athlete_registration_id')
            ->selectRaw('athlete_registrations.donation_event_id as event_id, SUM(donations.amount_per_round) as total')
            ->groupBy('athlete_registrations.donation_event_id')
            ->pluck('total', 'event_id');

        expect($newByEvent->all())->toBe($legacyByEvent->all());
    });
});

function seedHappyPath(object $testCase): array
{
    $athlete = Athlete::factory()->create([
        'donation_event_id' => $testCase->event->id,
        'partner_id' => $testCase->partner->id,
        'sport_type_id' => $testCase->sportType->id,
        'email' => 'baseline-athlete@example.com',
    ]);

    $donor = Donor::factory()->create([
        'email' => 'baseline-donor@example.com',
    ]);

    $donation = Donation::query()->create([
        'donor_id' => $donor->id,
        'athlete_id' => $athlete->id,
        'amount_per_round' => 9,
        'amount_max' => 90,
        'amount_min' => 0,
        'verified' => false,
    ]);

    return [$athlete, $donor, $donation];
}

function assertNoNewSchemaWrites(): void
{
    expect(ExternalUser::query()->count())->toBe(0)
        ->and(AthleteRegistration::query()->count())->toBe(0)
        ->and(Donation::query()->whereNotNull('donor_external_user_id')->count())->toBe(0)
        ->and(Donation::query()->whereNotNull('athlete_registration_id')->count())->toBe(0);
}
